<?php

namespace Tests\Mock;

use Fyre\FileSystem\File;
use RuntimeException;

use function json_encode;

class MockJob
{

    public static function run(array $arguments = [])
    {
        (new File('tmp/job', true))
            ->open('a')
            ->write(json_encode($arguments)."\r\n");
    }

    public static function fail()
    {
        return false;
    }

    public static function error()
    {
        throw new RuntimeException;
    }

}
