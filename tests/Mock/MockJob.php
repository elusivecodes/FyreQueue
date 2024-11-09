<?php

namespace Tests\Mock;

use Fyre\FileSystem\File;
use RuntimeException;

class MockJob
{
    public function error(): void
    {
        throw new RuntimeException();
    }

    public function fail(): false
    {
        return false;
    }

    public function run(int $test): void
    {
        (new File('tmp/job', true))
            ->open('a')
            ->write((string) $test);
    }
}
