<?php

namespace Tests\Mock;

use Fyre\Event\Event;
use Fyre\Event\EventListenerInterface;
use Fyre\FileSystem\File;
use Fyre\Queue\Message;
use Throwable;

use function serialize;

class MockListener implements EventListenerInterface
{
    public function exception(Event $event, Message $message, Throwable $exception, bool $retried): void
    {
        if ($retried) {
            return;
        }

        (new File('tmp/exception', true))
            ->open('a')
            ->write(serialize([
                'message' => $message,
                'exception' => $exception,
            ]));
    }

    public function failure(Event $event, Message $message, bool $retried): void
    {
        if ($retried) {
            return;
        }

        (new File('tmp/failure', true))
            ->open('a')
            ->write(serialize($message));
    }

    public function implementedEvents(): array
    {
        return [
            'Queue.exception' => 'exception',
            'Queue.failure' => 'failure',
            'Queue.invalid' => 'invalid',
            'Queue.start' => 'start',
            'Queue.success' => 'success',
        ];
    }

    public function invalid(Event $event, Message $message): void
    {
        (new File('tmp/invalid', true))
            ->open('a')
            ->write(serialize($message));
    }

    public function start(Event $event, Message $message): void
    {
        (new File('tmp/start', true))
            ->open('a')
            ->write(serialize($message));
    }

    public function success(Event $event, Message $message): void
    {
        (new File('tmp/success', true))
            ->open('a')
            ->write(serialize($message));
    }
}
