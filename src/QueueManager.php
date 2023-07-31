<?php
declare(strict_types=1);

namespace Fyre\Queue;

use Fyre\Queue\Exceptions\QueueException;

use function array_key_exists;
use function array_search;
use function class_exists;
use function is_array;

/**
 * QueueManager
 */
abstract class QueueManager
{

    public const DEFAULT = 'default';

    protected static array $config = [];

    protected static array $instances = [];

    /**
     * Clear all instances and configs.
     */
    public static function clear(): void
    {
        static::$config = [];
        static::$instances = [];
    }

    /**
     * Get the handler config.
     * @param string|null $key The config key.
     * @return array|null
     */
    public static function getConfig(string|null $key = null): array|null
    {
        if (!$key) {
            return static::$config;
        }

        return static::$config[$key] ?? null;
    }

    /**
     * Get the key for a queue instance.
     * @param Queue $queue The queue.
     * @return string|null The queue key.
     */
    public static function getKey(Queue $queue): string|null
    {
        return array_search($queue, static::$instances, true) ?: null;
    }

    /**
     * Determine if a config exists.
     * @param string $key The config key.
     * @return bool TRUE if the config exists, otherwise FALSE.
     */
    public static function hasConfig(string $key = self::DEFAULT): bool
    {
        return array_key_exists($key, static::$config);
    }

    /**
     * Determine if a handler is loaded.
     * @param string $key The config key.
     * @return bool TRUE if the handler is loaded, otherwise FALSE.
     */
    public static function isLoaded(string $key = self::DEFAULT): bool
    {
        return array_key_exists($key, static::$instances);
    }

    /**
     * Load a handler.
     * @param array $options Options for the handler.
     * @return Queue The handler.
     * @throws QueueException if the handler is invalid.
     */
    public static function load(array $options = []): Queue
    {
        if (!array_key_exists('className', $options)) {
            throw QueueException::forInvalidClass();
        }

        if (!class_exists($options['className'], true)) {
            throw QueueException::forInvalidClass($options['className']);
        }

        return new $options['className']($options);
    }

    /**
     * Push a job to the queue.
     * @param string $className The job class.
     * @param array $arguments The job arguments.
     * @param array $options The job options.
     */
    public static function push(string $className, array $arguments = [], array $options = []): void
    {
        $options['className'] = $className;
        $options['arguments'] = $arguments;

        $message = new Message($options);
        $config = $message->getConfig();

        static::use($config['config'])->push($config['queue'], $message);
    }

    /**
     * Set handler config.
     * @param string|array $key The config key.
     * @param array|null $options The config options.
     * @throws QueueException if the config is invalid.
     */
    public static function setConfig(string|array $key, array|null $options = null): void
    {
        if (is_array($key)) {
            foreach ($key AS $k => $value) {
                static::setConfig($k, $value);
            }

            return;
        }

        if (!is_array($options)) {
            throw QueueException::forInvalidConfig($key);
        }

        if (array_key_exists($key, static::$config)) {
            throw QueueException::forConfigExists($key);
        }

        static::$config[$key] = $options;
    }

    /**
     * Unload a handler.
     * @param string $key The config key.
     * @return bool TRUE if the handler was removed, otherwise FALSE.
     */
    public static function unload(string $key = self::DEFAULT): bool
    {
        if (!array_key_exists($key, static::$config)) {
            return false;
        }

        unset(static::$instances[$key]);
        unset(static::$config[$key]);

        return true;
    }

    /**
     * Load a shared handler instance.
     * @param string $key The config key.
     * @return Queue The handler.
     */
    public static function use(string $key = self::DEFAULT): Queue
    {
        return static::$instances[$key] ??= static::load(static::$config[$key] ?? []);
    }

}
