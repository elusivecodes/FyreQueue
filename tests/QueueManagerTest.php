<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Queue\Exceptions\QueueException;
use Fyre\Queue\Handlers\RedisQueue;
use Fyre\Queue\QueueManager;
use PHPUnit\Framework\TestCase;

final class QueueManagerTest extends TestCase
{
    public function testGetConfig(): void
    {
        $this->assertSame(
            [
                'default' => [
                    'className' => RedisQueue::class,
                ],
                'other' => [
                    'className' => RedisQueue::class,
                ],
            ],
            QueueManager::getConfig()
        );
    }

    public function testGetConfigKey(): void
    {
        $this->assertSame(
            [
                'className' => RedisQueue::class,
            ],
            QueueManager::getConfig('default')
        );
    }

    public function testGetKey(): void
    {
        $handler = QueueManager::use();

        $this->assertSame(
            'default',
            QueueManager::getKey($handler)
        );
    }

    public function testGetKeyInvalid(): void
    {
        $handler = QueueManager::load([
            'className' => RedisQueue::class,
        ]);

        $this->assertSame(
            null,
            QueueManager::getKey($handler)
        );
    }

    public function testIsLoaded(): void
    {
        QueueManager::use();

        $this->assertTrue(
            QueueManager::isLoaded()
        );
    }

    public function testIsLoadedInvalid(): void
    {
        $this->assertFalse(
            QueueManager::isLoaded('test')
        );
    }

    public function testIsLoadedKey(): void
    {
        QueueManager::use('other');

        $this->assertTrue(
            QueueManager::isLoaded('other')
        );
    }

    public function testLoad(): void
    {
        $this->assertInstanceOf(
            RedisQueue::class,
            QueueManager::load([
                'className' => RedisQueue::class,
            ])
        );
    }

    public function testLoadInvalidHandler(): void
    {
        $this->expectException(QueueException::class);

        QueueManager::load([
            'className' => 'Invalid',
        ]);
    }

    public function testSetConfig(): void
    {
        QueueManager::setConfig([
            'test' => [
                'className' => RedisQueue::class,
            ],
        ]);

        $this->assertSame(
            [
                'className' => RedisQueue::class,
            ],
            QueueManager::getConfig('test')
        );
    }

    public function testSetConfigExists(): void
    {
        $this->expectException(QueueException::class);

        QueueManager::setConfig('default', [
            'className' => RedisQueue::class,
        ]);
    }

    public function testUnload(): void
    {
        QueueManager::use();

        $this->assertTrue(
            QueueManager::unload()
        );

        $this->assertFalse(
            QueueManager::isLoaded()
        );
        $this->assertFalse(
            QueueManager::hasConfig()
        );
    }

    public function testUnloadInvalid(): void
    {
        $this->assertFalse(
            QueueManager::unload('test')
        );
    }

    public function testUnloadKey(): void
    {
        QueueManager::use('other');

        $this->assertTrue(
            QueueManager::unload('other')
        );

        $this->assertFalse(
            QueueManager::isLoaded('other')
        );
        $this->assertFalse(
            QueueManager::hasConfig('other')
        );
    }

    public function testUse(): void
    {
        $handler1 = QueueManager::use();
        $handler2 = QueueManager::use();

        $this->assertSame($handler1, $handler2);

        $this->assertInstanceOf(
            RedisQueue::class,
            $handler1
        );
    }

    protected function setUp(): void
    {
        QueueManager::clear();

        QueueManager::setConfig([
            'default' => [
                'className' => RedisQueue::class,
            ],
            'other' => [
                'className' => RedisQueue::class,
            ],
        ]);
    }
}
