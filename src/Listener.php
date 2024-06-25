<?php
declare(strict_types=1);

namespace Fyre\Queue;

use Throwable;

/**
 * Listener
 */
class Listener
{
    /**
     * Handle a Message exception.
     *
     * @param Message $message The Message.
     * @param Throwable $error The Exception.
     */
    public function exception(Message $message, Throwable $error): void {}

    /**
     * Handle a failed Message.
     *
     * @param Message $message The Message.
     */
    public function failure(Message $message): void {}

    /**
     * Handle an invalid Message.
     *
     * @param Message $message The Message.
     */
    public function invalid(Message $message): void {}

    /**
     * Handle a start Message.
     *
     * @param Message $message The Message.
     */
    public function start(Message $message): void {}

    /**
     * Handle a success Message.
     *
     * @param Message $message The Message.
     */
    public function success(Message $message): void {}
}
