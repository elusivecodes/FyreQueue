<?php
declare(strict_types=1);

namespace Fyre\Queue;

use Fyre\Container\Container;

use function array_replace;

/**
 * Queue
 */
abstract class Queue
{
    public const DEFAULT = 'default';

    protected static array $defaults = [];

    protected array $config;

    protected Container $container;

    /**
     * New Queue constructor.
     *
     * @param Container $container The Container;
     * @param array $options The queue options.
     */
    public function __construct(Container $container, array $options = [])
    {
        $this->container = $container;
        $this->config = array_replace(self::$defaults, static::$defaults, $options);
    }

    /**
     * Clear all items from the queue.
     *
     * @param string $queue The queue name.
     */
    abstract public function clear(string $queue = self::DEFAULT): void;

    /**
     * Mark a job as completed.
     *
     * @param Message $message The Message.
     */
    abstract public function complete(Message $message): void;

    /**
     * Mark a job as failed.
     *
     * @param Message $message The Message.
     * @return bool TRUE if the Message was retried, otherwise FALSE.
     */
    abstract public function fail(Message $message): bool;

    /**
     * Pop the last message off the queue.
     *
     * @param string $queue The queue name.
     * @return Message|null The last message.
     */
    abstract public function pop(string $queue = self::DEFAULT): Message|null;

    /**
     * Push a job onto the queue.
     *
     * @param Message $message The Message.
     * @return bool TRUE if the Message was added to the queue, otherwise FALSE.
     */
    abstract public function push(Message $message): bool;

    /**
     * Get all the active queues.
     *
     * @return array The active queues.
     */
    abstract public function queues(): array;

    /**
     * Reset the queue statistics.
     *
     * @param string $queue The queue name.
     */
    abstract public function reset(string $queue = self::DEFAULT): void;

    /**
     * Get the statistics for a queue
     *
     * @param string $queue The queue name.
     * @return array The queue statistics.
     */
    abstract public function stats(string $queue = self::DEFAULT): array;
}
