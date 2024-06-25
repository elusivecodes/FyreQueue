<?php

namespace Tests\Mock;

use Fyre\FileSystem\File;
use RuntimeException;

use function json_encode;

class MockJob
{
    public static function error(): void
    {
        throw new RuntimeException();
    }

    public static function fail(): false
    {
        return false;
    }

    public static function run(array $arguments = []): void
    {
        (new File('tmp/job', true))
            ->open('a')
            ->write(json_encode($arguments)."\r\n");
    }
}
