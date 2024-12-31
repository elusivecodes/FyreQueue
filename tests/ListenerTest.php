<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Config\Config;
use Fyre\Container\Container;
use Fyre\Event\EventManager;
use Fyre\FileSystem\File;
use Fyre\FileSystem\Folder;
use Fyre\Queue\Handlers\RedisQueue;
use Fyre\Queue\Message;
use Fyre\Queue\Queue;
use Fyre\Queue\QueueManager;
use Fyre\Queue\Worker;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Mock\MockJob;
use Tests\Mock\MockListener;

use function unserialize;

final class ListenerTest extends TestCase
{
    protected Container $container;

    protected Queue $queue;

    protected QueueManager $queueManager;

    public function testListenerException(): void
    {
        $this->queueManager->push(MockJob::class, ['test' => 1], [
            'method' => 'error',
            'retry' => false,
        ]);

        $this->assertSame(
            [
                'queued' => 1,
                'delayed' => 0,
                'completed' => 0,
                'failed' => 0,
                'total' => 1,
            ],
            $this->queue->stats()
        );

        $worker = $this->container->build(Worker::class, [
            'options' => [
                'maxJobs' => 1,
                'maxRuntime' => 5,
            ],
        ]);

        $worker->run();

        $this->assertSame(
            [
                'queued' => 0,
                'delayed' => 0,
                'completed' => 0,
                'failed' => 1,
                'total' => 1,
            ],
            $this->queue->stats()
        );

        $data = (new File('tmp/exception'))->contents();
        $data = unserialize($data);
        $message = $data['message'];
        $exception = $data['exception'];

        $this->assertInstanceOf(
            Message::class,
            $message
        );

        $this->assertSame(
            [
                'className' => MockJob::class,
                'method' => 'error',
                'arguments' => [
                    'test' => 1,
                ],
                'config' => 'default',
                'queue' => 'default',
                'after' => null,
                'before' => null,
                'retry' => false,
                'maxRetries' => 5,
                'unique' => false,
            ],
            $message->getConfig()
        );

        $this->assertInstanceOf(
            RuntimeException::class,
            $exception
        );
    }

    public function testListenerExceptionRetry(): void
    {
        $this->queueManager->push(MockJob::class, ['test' => 1], [
            'method' => 'error',
        ]);

        $this->assertSame(
            [
                'queued' => 1,
                'delayed' => 0,
                'completed' => 0,
                'failed' => 0,
                'total' => 1,
            ],
            $this->queue->stats()
        );

        $worker = $this->container->build(Worker::class, [
            'options' => [
                'maxJobs' => 5,
                'maxRuntime' => 5,
            ],
        ]);

        $worker->run();

        $this->assertSame(
            [
                'queued' => 0,
                'delayed' => 0,
                'completed' => 0,
                'failed' => 5,
                'total' => 5,
            ],
            $this->queue->stats()
        );

        $data = (new File('tmp/exception'))->contents();
        $data = unserialize($data);
        $message = $data['message'];
        $exception = $data['exception'];

        $this->assertInstanceOf(
            Message::class,
            $message
        );

        $this->assertSame(
            [
                'className' => MockJob::class,
                'method' => 'error',
                'arguments' => [
                    'test' => 1,
                ],
                'config' => 'default',
                'queue' => 'default',
                'after' => null,
                'before' => null,
                'retry' => true,
                'maxRetries' => 5,
                'unique' => false,
            ],
            $message->getConfig()
        );

        $this->assertInstanceOf(
            RuntimeException::class,
            $exception
        );
    }

    public function testListenerFailure(): void
    {
        $this->queueManager->push(MockJob::class, ['test' => 1], [
            'method' => 'fail',
            'retry' => false,
        ]);

        $this->assertSame(
            [
                'queued' => 1,
                'delayed' => 0,
                'completed' => 0,
                'failed' => 0,
                'total' => 1,
            ],
            $this->queue->stats()
        );

        $worker = $this->container->build(Worker::class, [
            'options' => [
                'maxJobs' => 1,
                'maxRuntime' => 5,
            ],
        ]);

        $worker->run();

        $this->assertSame(
            [
                'queued' => 0,
                'delayed' => 0,
                'completed' => 0,
                'failed' => 1,
                'total' => 1,
            ],
            $this->queue->stats()
        );

        $data = (new File('tmp/failure'))->contents();
        $message = unserialize($data);

        $this->assertInstanceOf(
            Message::class,
            $message
        );

        $this->assertSame(
            [
                'className' => MockJob::class,
                'method' => 'fail',
                'arguments' => [
                    'test' => 1,
                ],
                'config' => 'default',
                'queue' => 'default',
                'after' => null,
                'before' => null,
                'retry' => false,
                'maxRetries' => 5,
                'unique' => false,
            ],
            $message->getConfig()
        );
    }

    public function testListenerFailureRetry(): void
    {
        $this->queueManager->push(MockJob::class, ['test' => 1], [
            'method' => 'fail',
        ]);

        $this->assertSame(
            [
                'queued' => 1,
                'delayed' => 0,
                'completed' => 0,
                'failed' => 0,
                'total' => 1,
            ],
            $this->queue->stats()
        );

        $worker = $this->container->build(Worker::class, [
            'options' => [
                'maxJobs' => 5,
                'maxRuntime' => 5,
            ],
        ]);

        $worker->run();

        $this->assertSame(
            [
                'queued' => 0,
                'delayed' => 0,
                'completed' => 0,
                'failed' => 5,
                'total' => 5,
            ],
            $this->queue->stats()
        );

        $data = (new File('tmp/failure'))->contents();
        $message = unserialize($data);

        $this->assertInstanceOf(
            Message::class,
            $message
        );

        $this->assertSame(
            [
                'className' => MockJob::class,
                'method' => 'fail',
                'arguments' => [
                    'test' => 1,
                ],
                'config' => 'default',
                'queue' => 'default',
                'after' => null,
                'before' => null,
                'retry' => true,
                'maxRetries' => 5,
                'unique' => false,
            ],
            $message->getConfig()
        );
    }

    public function testListenerInvalid(): void
    {
        $this->queueManager->push('Invalid', ['test' => 1]);

        $this->assertSame(
            [
                'queued' => 1,
                'delayed' => 0,
                'completed' => 0,
                'failed' => 0,
                'total' => 1,
            ],
            $this->queue->stats()
        );

        $worker = $this->container->build(Worker::class, [
            'options' => [
                'maxJobs' => 1,
                'maxRuntime' => 5,
            ],
        ]);

        $worker->run();

        $this->assertSame(
            [
                'queued' => 0,
                'delayed' => 0,
                'completed' => 0,
                'failed' => 0,
                'total' => 1,
            ],
            $this->queue->stats()
        );

        $data = (new File('tmp/invalid'))->contents();
        $message = unserialize($data);

        $this->assertInstanceOf(
            Message::class,
            $message
        );

        $this->assertSame(
            [
                'className' => 'Invalid',
                'method' => 'run',
                'arguments' => [
                    'test' => 1,
                ],
                'config' => 'default',
                'queue' => 'default',
                'after' => null,
                'before' => null,
                'retry' => true,
                'maxRetries' => 5,
                'unique' => false,
            ],
            $message->getConfig()
        );
    }

    public function testListenerSuccess(): void
    {
        $this->queueManager->push(MockJob::class, ['test' => 1]);

        $this->assertSame(
            [
                'queued' => 1,
                'delayed' => 0,
                'completed' => 0,
                'failed' => 0,
                'total' => 1,
            ],
            $this->queue->stats()
        );

        $worker = $this->container->build(Worker::class, [
            'options' => [
                'maxJobs' => 1,
                'maxRuntime' => 5,
            ],
        ]);

        $worker->run();

        $this->assertSame(
            [
                'queued' => 0,
                'delayed' => 0,
                'completed' => 1,
                'failed' => 0,
                'total' => 1,
            ],
            $this->queue->stats()
        );

        $data = (new File('tmp/start'))->contents();
        $message = unserialize($data);

        $this->assertInstanceOf(
            Message::class,
            $message
        );

        $this->assertSame(
            [
                'className' => MockJob::class,
                'method' => 'run',
                'arguments' => [
                    'test' => 1,
                ],
                'config' => 'default',
                'queue' => 'default',
                'after' => null,
                'before' => null,
                'retry' => true,
                'maxRetries' => 5,
                'unique' => false,
            ],
            $message->getConfig()
        );

        $data = (new File('tmp/success'))->contents();
        $message = unserialize($data);

        $this->assertInstanceOf(
            Message::class,
            $message
        );

        $this->assertSame(
            [
                'className' => MockJob::class,
                'method' => 'run',
                'arguments' => [
                    'test' => 1,
                ],
                'config' => 'default',
                'queue' => 'default',
                'after' => null,
                'before' => null,
                'retry' => true,
                'maxRetries' => 5,
                'unique' => false,
            ],
            $message->getConfig()
        );
    }

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->container->singleton(Config::class);
        $this->container->singleton(EventManager::class);
        $this->container->singleton(QueueManager::class);

        $this->container->use(Config::class)->set('Queue', [
            'default' => [
                'className' => RedisQueue::class,
                'host' => getenv('REDIS_HOST'),
                'password' => getenv('REDIS_PASSWORD'),
                'database' => getenv('REDIS_DATABASE'),
                'port' => getenv('REDIS_PORT'),
            ],
        ]);

        $this->container->use(EventManager::class)->addListener(new MockListener());

        $this->queueManager = $this->container->use(QueueManager::class);
        $this->queue = $this->queueManager->use();
    }

    protected function tearDown(): void
    {
        $this->queue->clear();
        $this->queue->reset();

        $folder = new Folder('tmp');

        if ($folder->exists()) {
            $folder->delete();
        }
    }
}
