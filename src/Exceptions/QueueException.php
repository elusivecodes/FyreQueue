<?php
declare(strict_types=1);

namespace Fyre\Queue\Exceptions;

use RuntimeException;

/**
 * QueueException
 */
class QueueException extends RuntimeException
{
    public static function forAuthFailed(): static
    {
        return new static('Queue handler authentication failed');
    }

    public static function forConfigExists(string $key): static
    {
        return new static('Queue handler config already exists: '.$key);
    }

    public static function forConnectionError(string $message = ''): static
    {
        return new static('Queue handler connection error: '.$message);
    }

    public static function forConnectionFailed(): static
    {
        return new static('Queue handler connection failed');
    }

    public static function forInvalidClass(string $className = ''): static
    {
        return new static('Queue handler class not found: '.$className);
    }

    public static function forInvalidDatabase(string $database): static
    {
        return new static('Queue handler invalid database: '.$database);
    }

    public static function forInvalidKey(string $key): static
    {
        return new static('Queue handler invalid key: '.$key);
    }
}
