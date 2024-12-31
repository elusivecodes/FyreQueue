<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Config\Config;
use Fyre\Container\Container;
use Fyre\Event\EventManager;
use Fyre\FileSystem\File;
use Fyre\FileSystem\Folder;
use Fyre\Queue\Handlers\RedisQueue;
use Fyre\Queue\Queue;
use Fyre\Queue\QueueManager;
use Fyre\Queue\Worker;
use PHPUnit\Framework\TestCase;
use Tests\Mock\MockJob;
use Tests\Mock\MockListener;

use function getenv;

final class WorkerTest extends TestCase
{
    protected Container $container;

    protected Queue $queue;

    protected QueueManager $queueManager;

    public function testWorkerJob(): void
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

        $this->assertSame(
            ['default'],
            $this->queue->queues()
        );

        $this->assertSame(
            '1',
            (new File('tmp/job'))->contents()
        );
    }

    public function testWorkerJobWithDelay(): void
    {
        $this->queueManager->push(MockJob::class, ['test' => 1], [
            'delay' => 10,
        ]);

        $this->assertSame(
            [
                'queued' => 0,
                'delayed' => 1,
                'completed' => 0,
                'failed' => 0,
                'total' => 0,
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

        $this->assertFalse(
            (new File('tmp/job'))->exists()
        );

        sleep(5);

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

        $this->assertSame(
            ['default'],
            $this->queue->queues()
        );

        $this->assertSame(
            '1',
            (new File('tmp/job'))->contents()
        );
    }

    public function testWorkerJobWithExpires(): void
    {
        $this->queueManager->push(MockJob::class, ['test' => 1], [
            'expires' => -1,
        ]);

        $this->assertSame(
            [
                'queued' => 0,
                'delayed' => 0,
                'completed' => 0,
                'failed' => 0,
                'total' => 0,
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

        $this->assertFalse(
            (new File('tmp/job'))->exists()
        );
    }

    public function testWorkerJobWithQueue(): void
    {
        $this->queueManager->push(MockJob::class, ['test' => 1], [
            'queue' => 'test',
        ]);

        $this->assertSame(
            [
                'queued' => 0,
                'delayed' => 0,
                'completed' => 0,
                'failed' => 0,
                'total' => 0,
            ],
            $this->queue->stats()
        );

        $this->assertSame(
            [
                'queued' => 1,
                'delayed' => 0,
                'completed' => 0,
                'failed' => 0,
                'total' => 1,
            ],
            $this->queue->stats('test')
        );

        $worker = $this->container->build(Worker::class, [
            'options' => [
                'maxJobs' => 1,
                'maxRuntime' => 5,
            ],
        ]);

        $worker->run();

        $this->assertFalse(
            (new File('tmp/job'))->exists()
        );

        $worker = $this->container->build(Worker::class, [
            'options' => [
                'queue' => 'test',
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
            $this->queue->stats('test')
        );

        $this->assertSame(
            ['test'],
            $this->queue->queues()
        );

        $this->assertSame(
            '1',
            (new File('tmp/job'))->contents()
        );
    }

    public function testWorkerMultipleJobs(): void
    {
        $this->queueManager->push(MockJob::class, ['test' => 1]);
        $this->queueManager->push(MockJob::class, ['test' => 2]);

        $this->assertSame(
            [
                'queued' => 2,
                'delayed' => 0,
                'completed' => 0,
                'failed' => 0,
                'total' => 2,
            ],
            $this->queue->stats()
        );

        $worker = $this->container->build(Worker::class, [
            'options' => [
                'maxJobs' => 2,
                'maxRuntime' => 5,
            ],
        ]);

        $worker->run();

        $this->assertSame(
            [
                'queued' => 0,
                'delayed' => 0,
                'completed' => 2,
                'failed' => 0,
                'total' => 2,
            ],
            $this->queue->stats()
        );

        $this->assertSame(
            ['default'],
            $this->queue->queues()
        );

        $this->assertSame(
            '12',
            (new File('tmp/job'))->contents()
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
                'listeners' => [
                    MockListener::class,
                ],
                'host' => getenv('REDIS_HOST'),
                'password' => getenv('REDIS_PASSWORD'),
                'database' => getenv('REDIS_DATABASE'),
                'port' => getenv('REDIS_PORT'),
            ],
        ]);

        $this->queueManager = $this->container->use(QueueManager::class);
        $this->queue = $this->queueManager->use();
    }

    protected function tearDown(): void
    {
        $this->queue->clear();
        $this->queue->clear('test');

        $this->queue->reset();
        $this->queue->reset('test');

        $folder = new Folder('tmp');

        if ($folder->exists()) {
            $folder->delete();
        }
    }
}
