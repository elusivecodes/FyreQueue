<?php
declare(strict_types=1);

namespace Fyre\Queue;

use Fyre\Queue\Exceptions\QueueException;

use function array_replace;
use function class_exists;
use function is_a;

/**
 * Queue
 */
abstract class Queue
{

    protected static array $defaults = [
        'listener' => Listener::class
    ];

    protected Listener $listener;

    protected array $config;

    /**
     * New Queue constructor.
     * @param array $options The queue options.
     */
    public function __construct(array $options = [])
    {
        $this->config = array_replace(self::$defaults, static::$defaults, $options);

        if (!class_exists($this->config['listener']) || !is_a($this->config['listener'], Listener::class, true)) {
            throw QueueException::forInvalidListener($this->config['listener']);
        }
    }

    /**
     * Clear all items from the queue.
     * @param string $queue The queue name.
     * @param bool TRUE if the queue was cleared, otherwise FALSE.
     */
    abstract public function clear(string $queue): bool;

    /**
     * Get the queue Listener.
     * @return Listener The Listener.
     */
    public function getListener(): Listener
    {
        return $this->listener ??= new $this->config['listener'];
    }

    /**
     * Pop the last message off the queue.
     * @param string $queue The queue name.
     * @return Message|null The last message.
     */
    abstract public function pop(string $queue): Message|null;

    /**
     * Push a message onto the queue.
     * @param string $queue The queue name.
     * @param Message $message The Message.
     * @return Queue The Queue.
     */
    abstract public function push(string $queue, Message $message): static;

}
