<?php
declare(strict_types=1);

namespace Fyre\Queue\Exceptions;

use RuntimeException;

/**
 * QueueException
 */
class QueueException extends RuntimeException
{

    public static function forAuthFailed()
    {
        return new static('Queue handler authentication failed');
    }

    public static function forConfigExists(string $key)
    {
        return new static('Queue handler config already exists: '.$key);
    }

    public static function forConnectionError(string $message = '')
    {
        return new static('Queue handler connection error: '.$message);
    }

    public static function forConnectionFailed()
    {
        return new static('Queue handler connection failed');
    }

    public static function forInvalidClass(string $className = '')
    {
        return new static('Queue handler class not found: '.$className);
    }

    public static function forInvalidConfig(string $key)
    {
        return new static('Queue handler invalid config: '.$key);
    }

    public static function forInvalidDatabase(string $database)
    {
        return new static('Queue handler invalid database: '.$database);
    }

    public static function forInvalidKey(string $key)
    {
        return new static('Queue handler invalid key: '.$key);
    }

    public static function forInvalidListener(string $listener)
    {
        return new static('Queue handler invalid listener: '.$listener);
    }

}
