<?php
declare(strict_types=1);

namespace Fyre\Queue;

use Fyre\Config\Config;
use Fyre\Container\Container;
use Fyre\Queue\Exceptions\QueueException;

use function array_key_exists;
use function class_exists;
use function is_subclass_of;

/**
 * QueueManager
 */
class QueueManager
{
    public const DEFAULT = 'default';

    protected array $config = [];

    protected Container $container;

    protected array $instances = [];

    /**
     * New QueueManager constructor.
     *
     * @param Container $container The Container;
     */
    public function __construct(Container $container, Config $config)
    {
        $this->container = $container;

        $handlers = $config->get('Queue', []);

        foreach ($handlers as $key => $options) {
            $this->setConfig($key, $options);
        }
    }

    /**
     * Build a handler.
     *
     * @param array $options Options for the handler.
     * @return Queue The handler.
     *
     * @throws QueueException if the handler is not valid.
     */
    public function build(array $options = []): Queue
    {
        if (!array_key_exists('className', $options)) {
            throw QueueException::forInvalidClass();
        }

        if (!class_exists($options['className'], true) || !is_subclass_of($options['className'], Queue::class)) {
            throw QueueException::forInvalidClass($options['className']);
        }

        return $this->container->build($options['className'], ['options' => $options]);
    }

    /**
     * Clear all instances and configs.
     */
    public function clear(): void
    {
        $this->config = [];
        $this->instances = [];
    }

    /**
     * Get the handler config.
     *
     * @param string|null $key The config key.
     */
    public function getConfig(string|null $key = null): array|null
    {
        if (!$key) {
            return $this->config;
        }

        return $this->config[$key] ?? null;
    }

    /**
     * Determine whether a config exists.
     *
     * @param string $key The config key.
     * @return bool TRUE if the config exists, otherwise FALSE.
     */
    public function hasConfig(string $key = self::DEFAULT): bool
    {
        return array_key_exists($key, $this->config);
    }

    /**
     * Determine whether a handler is loaded.
     *
     * @param string $key The config key.
     * @return bool TRUE if the handler is loaded, otherwise FALSE.
     */
    public function isLoaded(string $key = self::DEFAULT): bool
    {
        return array_key_exists($key, $this->instances);
    }

    /**
     * Push a job to the queue.
     *
     * @param string $className The job class.
     * @param array $arguments The job arguments.
     * @param array $options The job options.
     * @return QueueManager The QueueManager.
     */
    public function push(string $className, array $arguments = [], array $options = []): static
    {
        $options['className'] = $className;
        $options['arguments'] = $arguments;

        $message = new Message($options);
        $config = $message->getConfig();

        $this->use($config['config'])->push($message);

        return $this;
    }

    /**
     * Set handler config.
     *
     * @param string $key The config key.
     * @param array $options The config options.
     * @return QueueManager The QueueManager.
     *
     * @throws QueueException if the config is not valid.
     */
    public function setConfig(string $key, array $options): static
    {
        if (array_key_exists($key, $this->config)) {
            throw QueueException::forConfigExists($key);
        }

        $this->config[$key] = $options;

        return $this;
    }

    /**
     * Unload a handler.
     *
     * @param string $key The config key.
     * @return QueueManager The QueueManager.
     */
    public function unload(string $key = self::DEFAULT): static
    {
        unset($this->instances[$key]);
        unset($this->config[$key]);

        return $this;
    }

    /**
     * Load a shared handler instance.
     *
     * @param string $key The config key.
     * @return Queue The handler.
     */
    public function use(string $key = self::DEFAULT): Queue
    {
        return $this->instances[$key] ??= $this->build($this->config[$key] ?? []);
    }
}
