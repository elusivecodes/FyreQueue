<?php
declare(strict_types=1);

namespace Tests;

use Fyre\FileSystem\File;
use Fyre\FileSystem\Folder;
use Fyre\Queue\Handlers\RedisQueue;
use Fyre\Queue\Message;
use Fyre\Queue\QueueManager;
use Fyre\Queue\Worker;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Mock\MockJob;
use Tests\Mock\MockListener;

use function unserialize;

final class ListenerTest extends TestCase
{

    public function testListenerSuccess(): void
    {
        QueueManager::push(MockJob::class, ['test' => 1]);

        $worker = new Worker([
            'maxJobs' => 1,
            'maxRuntime' => 5
        ]);

        $worker->run();

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
                    'test' => 1
                ],
                'config' => 'default',
                'queue' => 'default',
                'after' => null,
                'before' => null
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
                    'test' => 1
                ],
                'config' => 'default',
                'queue' => 'default',
                'after' => null,
                'before' => null
            ],
            $message->getConfig()
        );
    }

    public function testListenerFailure(): void
    {
        QueueManager::push(MockJob::class, ['test' => 1], [
            'method' => 'fail'
        ]);

        $worker = new Worker([
            'maxJobs' => 1,
            'maxRuntime' => 5
        ]);

        $worker->run();

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
                    'test' => 1
                ],
                'config' => 'default',
                'queue' => 'default',
                'after' => null,
                'before' => null
            ],
            $message->getConfig()
        );
    }

    public function testListenerException(): void
    {
        QueueManager::push(MockJob::class, ['test' => 1], [
            'method' => 'error'
        ]);

        $worker = new Worker([
            'maxJobs' => 1,
            'maxRuntime' => 5
        ]);

        $worker->run();

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
                    'test' => 1
                ],
                'config' => 'default',
                'queue' => 'default',
                'after' => null,
                'before' => null
            ],
            $message->getConfig()
        );

        $this->assertInstanceOf(
            RuntimeException::class,
            $exception
        );
    }

    public function testListenerInvalid(): void
    {
        QueueManager::push('Invalid', ['test' => 1]);

        $worker = new Worker([
            'maxJobs' => 1,
            'maxRuntime' => 5
        ]);

        $worker->run();

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
                    'test' => 1
                ],
                'config' => 'default',
                'queue' => 'default',
                'after' => null,
                'before' => null
            ],
            $message->getConfig()
        );
    }

    protected function setUp(): void
    {
        QueueManager::clear();
        QueueManager::setConfig('default', [
            'className' => RedisQueue::class,
            'listener' => MockListener::class
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
