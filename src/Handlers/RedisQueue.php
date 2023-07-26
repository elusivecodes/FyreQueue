<?php
declare(strict_types=1);

namespace Fyre\Queue\Handlers;

use Fyre\Queue\Exceptions\QueueException;
use Fyre\Queue\Message;
use Fyre\Queue\Queue;
use Redis;
use RedisException;

use function serialize;
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
        'timeout' => 0
    ];

    protected Redis $connection;

    /**
     * New Queue constructor.
     * @param array $options The queue options.
     */
    public function __construct(array $options)
    {
        parent::__construct($options);

        try {
            $this->connection = new Redis();
    
            if (!$this->connection->connect($this->config['host'], (int) $this->config['port'], $this->config['timeout'])) {
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
     * @param string $queue The queue name.
     * @param bool TRUE if the queue was cleared, otherwise FALSE.
     */
    public function clear(string $queue): bool
    {
        return $this->connection->del($queue) === 1;
    }

    /**
     * Pop the last message off the queue.
     * @param string $queue The queue name.
     * @return Message|null The last message.
     */
    public function pop(string $queue): Message|null
    {
        $item = $this->connection->lpop($queue);

        return $item ?
            unserialize($item) :
            null;
    }

    /**
     * Push a message onto the queue.
     * @param string $queue The queue name.
     * @param Message $message The Message.
     * @return Queue The Queue.
     */
    public function push(string $queue, Message $message): static
    {
        $this->connection->rpush($queue, serialize($message));

        return $this;
    }

}
