<?php
declare(strict_types=1);

namespace Fyre\Queue;

use
    Throwable;

use function
    array_replace_recursive,
    time,
    usleep;

/**
 * Worker
 */
class Worker
{

    protected static array $defaults = [
        'config' => 'default',
        'queue' => 'default',
        'maxJobs' => 0,
        'maxRuntime' => 0
    ];

    protected Queue $queue;

    protected Listener $listener;

    protected array $config;

    protected int $jobCount = 0;

    protected int $start;

    /**
     * New Worker constructor.
     * @param array $options The worker options.
     */
    public function __construct(array $options = [])
    {
        $this->config = array_replace_recursive(self::$defaults, static::$defaults, $options);

        $this->queue = QueueManager::use($this->config['config']);
        $this->listener = $this->queue->getListener();
    }

    /**
     * Run the worker.
     */
    public function run(): void
    {
        $this->start = time();
        $this->jobCount = 0;

        while (true) {
            if ($this->config['maxJobs'] && $this->jobCount >= $this->config['maxJobs']) {
                break;
            }

            if ($this->config['maxRuntime'] && time() - $this->start >= $this->config['maxRuntime']) {
                break;
            }

            $message = $this->queue->pop($this->config['queue']);
    
            if ($message) {
                $this->process($message);
            }
    
            usleep(1000);
        }
    }

    /**
     * Process a Message.
     * @param Message $message The Message.
     */
    protected function process(Message $message): void
    {
        if (!$message->isValid()) {
            $this->listener->invalid($message);
            return;
        }

        if ($message->isExpired()) {
            return;
        }

        if (!$message->isReady()) {
            $config = $message->getConfig();

            $this->queue->push($config['queue'], $message);
            return;
        }

        try {
            $this->listener->start($message);

            $callback = $message->getCallback();
            $arguments = $message->getArguments();

            if ($callback($arguments) === false) {
                $this->listener->failure($message);
            } else {
                $this->listener->success($message);
            }
        } catch (Throwable $e) {
            $this->listener->exception($message, $e);
        }

        $this->jobCount++;
    }

}
