<?php
declare(strict_types=1);

namespace Tests;

use
    Fyre\FileSystem\File,
    Fyre\FileSystem\Folder,
    Fyre\Queue\Handlers\RedisQueue,
    Fyre\Queue\QueueManager,
    Fyre\Queue\Worker,
    PHPUnit\Framework\TestCase,
    Tests\Mock\MockJob;

final class WorkerTest extends TestCase
{

    public function testWorkerJob(): void
    {
        QueueManager::push(MockJob::class, ['test' => 1]);

        $worker = new Worker([
            'maxJobs' => 1,
            'maxRuntime' => 5
        ]);

        $worker->run();

        $this->assertSame(
            '{"test":1}'."\r\n",
            (new File('tmp/job'))->contents()
        );
    }

    public function testWorkerMultipleJobs(): void
    {
        QueueManager::push(MockJob::class, ['test' => 1]);
        QueueManager::push(MockJob::class, ['test' => 2]);

        $worker = new Worker([
            'maxJobs' => 2,
            'maxRuntime' => 5
        ]);

        $worker->run();

        $this->assertSame(
            '{"test":1}'."\r\n".
            '{"test":2}'."\r\n",
            (new File('tmp/job'))->contents()
        );
    }

    public function testWorkerJobWithDelay(): void
    {
        QueueManager::push(MockJob::class, ['test' => 1], [
            'delay' => 10
        ]);

        $worker = new Worker([
            'maxJobs' => 1,
            'maxRuntime' => 5
        ]);

        $worker->run();

        $this->assertFalse(
            (new File('tmp/job'))->exists()
        );

        sleep(5);

        $worker = new Worker([
            'maxJobs' => 1,
            'maxRuntime' => 5
        ]);

        $worker->run();

        $this->assertSame(
            '{"test":1}'."\r\n",
            (new File('tmp/job'))->contents()
        );
    }

    public function testWorkerJobWithExpires(): void
    {
        QueueManager::push(MockJob::class, ['test' => 1], [
            'expires' => -1
        ]);

        $worker = new Worker([
            'maxJobs' => 1,
            'maxRuntime' => 5
        ]);

        $worker->run();

        $this->assertFalse(
            (new File('tmp/job'))->exists()
        );
    }

    public function testWorkerJobWithQueue(): void
    {
        QueueManager::push(MockJob::class, ['test' => 1], [
            'queue' => 'test'
        ]);

        $worker = new Worker([
            'maxJobs' => 1,
            'maxRuntime' => 5
        ]);

        $worker->run();

        $this->assertFalse(
            (new File('tmp/job'))->exists()
        );

        $worker = new Worker([
            'queue' => 'test',
            'maxJobs' => 1,
            'maxRuntime' => 5
        ]);

        $worker->run();

        $this->assertSame(
            '{"test":1}'."\r\n",
            (new File('tmp/job'))->contents()
        );
    }

    protected function setUp(): void
    {
        QueueManager::clear();
        QueueManager::setConfig('default', [
            'className' => RedisQueue::class
        ]);

        QueueManager::use()->clear('default');
    }

    protected function tearDown(): void
    {
        $folder = new Folder('tmp');

        if ($folder->exists()) {
            $folder->delete();
        }
    }

}
