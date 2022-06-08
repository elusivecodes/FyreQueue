<?php

namespace Tests\Mock;

use
    Fyre\FileSystem\File,
    Fyre\Queue\Listener,
    Fyre\Queue\Message,
    Throwable;

use function
    serialize;

class MockListener extends Listener
{

    public function exception(Message $message, Throwable $exception): void
    {
        (new File('tmp/exception', true))
            ->open('a')
            ->write(serialize([
                'message' => $message,
                'exception' => $exception
            ]));
    }

    public function failure(Message $message): void
    {
        (new File('tmp/failure', true))
            ->open('a')
            ->write(serialize($message));
    }

    public function invalid(Message $message): void
    {
        (new File('tmp/invalid', true))
            ->open('a')
            ->write(serialize($message));
    }

    public function start(Message $message): void
    {
        (new File('tmp/start', true))
            ->open('a')
            ->write(serialize($message));
    }

    public function success(Message $message): void
    {
        (new File('tmp/success', true))
            ->open('a')
            ->write(serialize($message));
    }

}
