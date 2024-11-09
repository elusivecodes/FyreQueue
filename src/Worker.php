<?php
declare(strict_types=1);

namespace Fyre\Queue;

use Fyre\Container\Container;
use Throwable;

use function array_replace;
use function method_exists;
use function pcntl_async_signals;
use function pcntl_signal;
use function time;
use function usleep;

use const SIG_DFL;
use const SIGQUIT;
use const SIGTERM;

/**
 * Worker
 */
class Worker
{
    protected static array $defaults = [
        'config' => QueueManager::DEFAULT,
        'queue' => Queue::DEFAULT,
        'maxJobs' => 0,
        'maxRuntime' => 0,
        'rest' => 10000,
        'sleep' => 1000000,
    ];

    protected array $config;

    protected Container $container;

    protected int $jobCount = 0;

    protected array $listeners;

    protected Queue $queue;

    protected int|null $start = null;

    /**
     * New Worker constructor.
     *
     * @param Container $container The Container.
     * @param QueueManager $queueManager The QueueManager.
     * @param array $options The worker options.
     */
    public function __construct(Container $container, QueueManager $queueManager, array $options = [])
    {
        $this->container = $container;

        $this->config = array_replace(self::$defaults, static::$defaults, $options);

        $this->queue = $queueManager->use($this->config['config']);
        $this->listeners = $this->queue->getListeners();
    }

    /**
     * Run the worker.
     */
    public function run(): void
    {
        if ($this->start !== null) {
            return;
        }

        $this->start = time();
        $this->jobCount = 0;

        $running = true;
        $stop = function() use (&$running): void {
            $running = false;
        };

        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, $stop);
        pcntl_signal(SIGQUIT, $stop);

        while ($running) {
            if ($this->config['maxJobs'] && $this->jobCount >= $this->config['maxJobs']) {
                break;
            }

            if ($this->config['maxRuntime'] && time() - $this->start >= $this->config['maxRuntime']) {
                break;
            }

            $message = $this->queue->pop($this->config['queue']);

            if ($message) {
                $this->process($message);

                usleep($this->config['rest']);
            } else {
                usleep($this->config['sleep']);
            }
        }

        pcntl_signal(SIGTERM, SIG_DFL);
        pcntl_signal(SIGQUIT, SIG_DFL);
    }

    /**
     * Handle an event callbacks.
     *
     * @param string $event The event.
     * @param array $arguments The event arguments.
     */
    protected function handleEvent(string $event, array $arguments): void
    {
        foreach ($this->listeners as $listener) {
            if (!method_exists($listener, $event)) {
                continue;
            }

            $listener->$event(...$arguments);
        }
    }

    /**
     * Process a Message.
     *
     * @param Message $message The Message.
     */
    protected function process(Message $message): void
    {
        if (!$message->isValid()) {
            $this->handleEvent('invalid', [$message]);

            return;
        }

        if ($message->isExpired()) {
            return;
        }

        $config = $message->getConfig();

        try {
            $this->handleEvent('start', [$message]);

            $instance = $this->container->build($config['className']);
            $method = $config['method'];

            $result = $this->container->call([$instance, $method], $config['arguments']);

            if ($result === false) {
                $retried = $this->queue->fail($message);

                $this->handleEvent('failure', [$message, $retried]);
            } else {
                $this->queue->complete($message);

                $this->handleEvent('success', [$message]);
            }
        } catch (Throwable $e) {
            $retried = $this->queue->fail($message);

            $this->handleEvent('exception', [$message, $e, $retried]);
        }

        $this->jobCount++;
    }
}
