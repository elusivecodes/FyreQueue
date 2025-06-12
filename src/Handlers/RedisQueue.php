<?php
declare(strict_types=1);

namespace Fyre\Queue\Handlers;

use Fyre\Container\Container;
use Fyre\Queue\Exceptions\QueueException;
use Fyre\Queue\Message;
use Fyre\Queue\Queue;
use Redis;
use RedisException;

use function array_shift;
use function count;
use function explode;
use function in_array;
use function serialize;
use function time;
use function unserialize;

/**
 * RedisQueue
 */
class RedisQueue extends Queue
{
    protected static array $defaults = [
        'host' => '127.0.0.1',
        'password' => null,
        'port' => 6379,
        'database' => null,
        'timeout' => 0,
        'persist' => true,
        'tls' => false,
        'ssl' => [
            'key' => null,
            'cert' => null,
            'ca' => null,
        ],
    ];

    protected Redis $connection;

    /**
     * New Queue constructor.
     *
     * @param Container $container The Container;
     * @param array $options The queue options.
     *
     * @throws QueueException if the connection is not valid.
     */
    public function __construct(Container $container, array $options = [])
    {
        parent::__construct($container, $options);

        try {
            $this->connection = new Redis();

            $tls = $this->config['tls'] ? 'tls://' : '';

            if (!$this->connection->connect(
                $tls.$this->config['host'],
                (int) $this->config['port'],
                (int) $this->config['timeout'],
                $this->config['persist'] ?
                    ($this->config['port'].$this->config['timeout'].$this->config['database']) :
                null,
                0,
                0,
                [
                    'ssl' => [
                        'local_pk' => $this->config['ssl']['key'] ?? null,
                        'local_cert' => $this->config['ssl']['cert'] ?? null,
                        'cafile' => $this->config['ssl']['ca'] ?? null,
                    ],
                ],
            )) {
                throw QueueException::forConnectionFailed();
            }

            if ($this->config['password'] && !$this->connection->auth($this->config['password'])) {
                throw QueueException::forAuthFailed();
            }

            if ($this->config['database'] && !$this->connection->select($this->config['database'])) {
                throw QueueException::forInvalidDatabase($this->config['database']);
            }

        } catch (RedisException $e) {
            throw QueueException::forConnectionError($e->getMessage());
        }
    }

    /**
     * Queue destructor.
     */
    public function __destruct()
    {
        $this->connection->close();
    }

    /**
     * Clear all items from the queue.
     *
     * @param string $queue The queue name.
     * @param bool TRUE if the queue was cleared, otherwise FALSE.
     */
    public function clear(string $queue = self::DEFAULT): void
    {
        $this->connection->del(static::prepareKey($queue));
        $this->connection->del(static::prepareKey($queue, 'unique'));
        $this->connection->zRemRangeByScore(static::prepareKey($queue, 'delayed'), '-inf', '+inf');
    }

    /**
     * Mark a Message as completed.
     *
     * @param Message $message The Message.
     */
    public function complete(Message $message): void
    {
        $queue = $message->getQueue();

        $this->connection->incrBy(static::prepareKey($queue, 'completed'), 1);
    }

    /**
     * Mark a Message as failed.
     *
     * @param Message $message The Message.
     * @return bool TRUE if the Message was retried, otherwise FALSE.
     */
    public function fail(Message $message): bool
    {
        $queue = $message->getQueue();

        $this->connection->incrBy(static::prepareKey($queue, 'failed'), 1);

        if (!$message->shouldRetry()) {
            return false;
        }

        return $this->push($message);
    }

    /**
     * Pop the last message off the queue.
     *
     * @param string $queue The queue name.
     * @return Message|null The last message.
     */
    public function pop(string $queue = self::DEFAULT): Message|null
    {
        // check for delayed messages
        $this->connection->watch(static::prepareKey($queue, 'delayed'));

        $itemsReady = $this->connection->zRangeByScore(static::prepareKey($queue, 'delayed'), '0', (string) time());

        if ($itemsReady !== []) {
            $this->connection->multi();

            foreach ($itemsReady as $data) {
                $this->connection->lPush(static::prepareKey($queue), $data);
            }

            $this->connection->zRem(static::prepareKey($queue, 'delayed'), $data);
            $this->connection->incrBy(static::prepareKey($queue, 'total'), count($itemsReady));
            $this->connection->exec();
        } else {
            $this->connection->unwatch();
        }

        // get the next message
        $data = $this->connection->rPop(static::prepareKey($queue));

        if (!$data) {
            return null;
        }

        $message = unserialize($data);

        if ($message->isUnique()) {
            $this->connection->hDel(static::prepareKey($queue, 'unique'), $message->getHash());
        }

        return $message;
    }

    /**
     * Push a message onto the queue.
     *
     * @param Message $message The Message.
     * @return bool TRUE if the Message was added to the queue, otherwise FALSE.
     */
    public function push(Message $message): bool
    {
        if ($message->isExpired()) {
            return false;
        }

        $queue = $message->getQueue();

        if ($message->isUnique()) {
            $uniqueKey = static::prepareKey($queue, 'unique');
            $messageHash = $message->getHash();

            if ($this->connection->hExists($uniqueKey, $messageHash)) {
                return false;
            }

            $this->connection->hSet($uniqueKey, $messageHash, 1);
        }

        $data = serialize($message);

        if (!$message->isReady()) {
            $this->connection->zAdd(static::prepareKey($queue, 'delayed'), $message->getAfter(), $data);
        } else {
            $this->connection->lPush(static::prepareKey($queue), $data);
            $this->connection->incrBy(static::prepareKey($queue, 'total'), 1);
        }

        return true;
    }

    /**
     * Get all the active queues.
     *
     * @return array The active queues.
     */
    public function queues(): array
    {
        $keys = $this->connection->keys(static::prepareKey('*'));

        $queues = [];

        foreach ($keys as $key) {
            $values = explode(':', $key);

            if (count($values) > 1) {
                array_shift($values);
            }

            if ($values[0] && !in_array($values[0], $queues)) {
                $queues[] = $values[0];
            }
        }

        return $queues;
    }

    /**
     * Reset the queue statistics.
     *
     * @param string $queue The queue name.
     */
    public function reset(string $queue = self::DEFAULT): void
    {
        $this->connection->del(static::prepareKey($queue, 'completed'));
        $this->connection->del(static::prepareKey($queue, 'failed'));
        $this->connection->del(static::prepareKey($queue, 'total'));
    }

    /**
     * Get the statistics for a queue.
     *
     * @param string $queue The queue name.
     * @return array The statistics.
     */
    public function stats(string $queue = self::DEFAULT): array
    {
        return [
            'queued' => (int) $this->connection->lLen(static::prepareKey($queue)),
            'delayed' => (int) $this->connection->zCount(static::prepareKey($queue, 'delayed'), '-inf', '+inf'),
            'completed' => (int) $this->connection->get(static::prepareKey($queue, 'completed')),
            'failed' => (int) $this->connection->get(static::prepareKey($queue, 'failed')),
            'total' => (int) $this->connection->get(static::prepareKey($queue, 'total')),
        ];
    }

    /**
     * Get the key for a queue with optional suffix.
     *
     * @param string $queue The queue name.
     * @param string $suffix The key suffix.
     * @return string The key.
     */
    protected static function prepareKey(string $queue, string $suffix = ''): string
    {
        return 'queue:'.$queue.($suffix ? ':'.$suffix : '');
    }
}
