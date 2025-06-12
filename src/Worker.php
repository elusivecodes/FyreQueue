<?php
declare(strict_types=1);

namespace Fyre\Queue;

use Fyre\Container\Container;
use Fyre\Event\EventDispatcherTrait;
use Fyre\Event\EventManager;
use Throwable;

use function array_replace;
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
    use EventDispatcherTrait;

    protected static array $defaults = [
        'config' => QueueManager::DEFAULT,
        'queue' => Queue::DEFAULT,
        'maxJobs' => 0,
        'maxRuntime' => 0,
        'rest' => 10000,
        'sleep' => 1000000,
    ];

    protected array $config;

    protected int $jobCount = 0;

    protected array $listeners;

    protected Queue $queue;

    protected int|null $start = null;

    /**
     * New Worker constructor.
     *
     * @param Container $container The Container.
     * @param QueueManager $queueManager The QueueManager.
     * @param EventManager $eventManager The EventManager.
     * @param array $options The worker options.
     */
    public function __construct(
        protected Container $container,
        QueueManager $queueManager,
        protected EventManager $eventManager,
        array $options = []
    ) {
        $this->container = $container;
        $this->eventManager = $eventManager;

        $this->config = array_replace(static::$defaults, $options);

        $this->queue = $queueManager->use($this->config['config']);
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
     * Process a Message.
     *
     * @param Message $message The Message.
     */
    protected function process(Message $message): void
    {
        if (!$message->isValid()) {
            $this->dispatchEvent('Queue.invalid', ['message' => $message], false);

            return;
        }

        if ($message->isExpired()) {
            return;
        }

        $config = $message->getConfig();

        try {
            $this->dispatchEvent('Queue.start', ['message' => $message], false);

            $this->container->clearScoped();

            $result = $this->container->call([$config['className'], $config['method']], $config['arguments']);

            if ($result === false) {
                $retried = $this->queue->fail($message);

                $this->dispatchEvent('Queue.failure', ['message' => $message, 'shouldRetry' => $retried], false);
            } else {
                $this->queue->complete($message);

                $this->dispatchEvent('Queue.success', ['message' => $message], false);
            }
        } catch (Throwable $e) {
            $retried = $this->queue->fail($message);

            $this->dispatchEvent('Queue.exception', ['message' => $message, 'exception' => $e, 'shouldRetry' => $retried], false);
        }

        $this->jobCount++;
    }
}
