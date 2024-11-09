<?php
declare(strict_types=1);

namespace Fyre\Queue;

use function class_exists;
use function implode;
use function json_encode;
use function ksort;
use function md5;
use function method_exists;
use function time;

/**
 * Message
 */
class Message
{
    protected static array $defaults = [
        'className' => null,
        'method' => 'run',
        'arguments' => [],
        'config' => QueueManager::DEFAULT,
        'queue' => Queue::DEFAULT,
        'delay' => 0,
        'expires' => 0,
        'after' => null,
        'before' => null,
        'retry' => true,
        'maxRetries' => 5,
        'unique' => false,
    ];

    protected array $config;

    protected int $retryAttempts = 0;

    /**
     * New Message constructor.
     *
     * @param array $options The message options.
     */
    public function __construct(array $options = [])
    {
        $this->config = array_replace(self::$defaults, static::$defaults, $options);

        if ($this->config['expires']) {
            $this->config['before'] ??= time() + $this->config['expires'];
        }

        if ($this->config['delay']) {
            $this->config['after'] ??= time() + $this->config['delay'];
        }

        unset($this->config['expires']);
        unset($this->config['delay']);
    }

    /**
     * Get the timestamp when the message can be sent.
     *
     * @return int|null The timestamp when the message can be sent.
     */
    public function getAfter(): int|null
    {
        return $this->config['after'];
    }

    /**
     * Get the message config.
     *
     * @return array The message config.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get the message hash.
     *
     * @return string The message hash.
     */
    public function getHash(): string
    {
        $arguments = $this->config['arguments'];

        ksort($arguments);

        $hashInput = implode([
            $this->config['className'],
            $this->config['method'],
            json_encode($arguments),
        ]);

        return md5($hashInput);
    }

    /**
     * Get the message queue.
     *
     * @return string The message queue.
     */
    public function getQueue(): string
    {
        return $this->config['queue'];
    }

    /**
     * Determine whether the message has expired.
     *
     * @return bool TRUE if the message has expired, otherwise FALSE.
     */
    public function isExpired(): bool
    {
        if (!$this->config['before']) {
            return false;
        }

        return $this->config['before'] < time();
    }

    /**
     * Determine whether the message is ready.
     *
     * @return bool TRUE if the message is ready, otherwise FALSE.
     */
    public function isReady(): bool
    {
        if ($this->config['after'] === null) {
            return true;
        }

        return $this->config['after'] < time();
    }

    /**
     * Determine whether the message must be unique.
     *
     * @return bool TRUE if the message must be unique, otherwise FALSE.
     */
    public function isUnique(): bool
    {
        return $this->config['unique'];
    }

    /**
     * Determine whether the message is valid.
     *
     * @return bool TRUE if the message is valid, otherwise FALSE.
     */
    public function isValid(): bool
    {
        return class_exists($this->config['className']) && method_exists($this->config['className'], $this->config['method']);
    }

    /**
     * Determine whether the message should be retried.
     *
     * @return bool TRUE if the message should be retried, otherwise FALSE.
     */
    public function shouldRetry(): bool
    {
        if (!$this->config['retry'] || $this->isExpired()) {
            return false;
        }

        return $this->retryAttempts++ < $this->config['maxRetries'] - 1;
    }
}
