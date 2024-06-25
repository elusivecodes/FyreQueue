<?php
declare(strict_types=1);

namespace Fyre\Queue;

use Closure;

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
        'config' => 'default',
        'queue' => 'default',
        'expires' => 0,
        'delay' => 0,
        'after' => null,
        'before' => null,
        'unique' => false,
    ];

    protected array $config;

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
     * Get the message arguments.
     *
     * @return array The message arguments.
     */
    public function getArguments(): array
    {
        return $this->config['arguments'];
    }

    /**
     * Get the message callback.
     *
     * @return Closure The message callback.
     */
    public function getCallback(): Closure
    {
        return Closure::fromCallable([$this->config['className'], $this->config['method']]);
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
     * Determine if the message has expired.
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
     * Determine if the message is ready.
     *
     * @return bool TRUE if the message is ready, otherwise FALSE.
     */
    public function isReady(): bool
    {
        if (!$this->config['after']) {
            return true;
        }

        return $this->config['after'] < time();
    }

    /**
     * Determine if the message is unique.
     *
     * @return bool TRUE if the message is unique, otherwise FALSE.
     */
    public function isUnique(): bool
    {
        return $this->config['unique'];
    }

    /**
     * Determine if the message is valid.
     *
     * @return bool TRUE if the message is valid, otherwise FALSE.
     */
    public function isValid(): bool
    {
        return class_exists($this->config['className']) && method_exists($this->config['className'], $this->config['method']);
    }
}
