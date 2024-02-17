<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Queue\Message;
use PHPUnit\Framework\TestCase;
use Tests\Mock\MockJob;

use function unserialize;

final class MessageTest extends TestCase
{

    public function testMessageUnique(): void
    {
        $message = new Message([
            'unique' => true
        ]);

        $this->assertTrue(
            $message->isUnique()
        );
    }

    public function testMessageHash(): void
    {
        $message1 = new Message([
            'className' => MockJob::class,
            'method' => 'run',
            'arguments' => [
                'test' => 1,
                'other' => 2
            ]
        ]);

        $message2 = new Message([
            'className' => MockJob::class,
            'method' => 'run',
            'arguments' => [
                'other' => 2,
                'test' => 1
            ]
        ]);

        $this->assertSame(
            '06c15dae80535a3aa6fbb479e077103b',
            $message1->getHash()
        );

        $this->assertSame(
            $message1->getHash(),
            $message2->getHash()
        );
    }

}
