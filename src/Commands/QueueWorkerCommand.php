<?php
declare(strict_types=1);

namespace Fyre\Queue\Commands;

use Fyre\Command\Command;
use Fyre\Console\Console;
use Fyre\Container\Container;
use Fyre\Queue\Queue;
use Fyre\Queue\QueueManager;
use Fyre\Queue\Worker;
use RuntimeException;

use function pcntl_fork;

/**
 * QueueWorkerCommand
 */
class QueueWorkerCommand extends Command
{
    protected string|null $alias = 'queue:worker';

    protected string $description = 'Start a background queue worker.';

    protected array $options = [
        'config' => [
            'default' => QueueManager::DEFAULT,
        ],
        'queue' => [
            'default' => Queue::DEFAULT,
        ],
        'maxJobs' => [
            'as' => 'integer',
            'default' => 0,
        ],
        'maxRuntime' => [
            'as' => 'integer',
            'default' => 0,
        ],
    ];

    /**
     * Run the command.
     *
     * @param Container $container The Container.
     * @param Console $io The Console.
     * @param string $config The queue config key.
     * @param string $queue The queue name.
     * @param int $maxJobs The maximum number of jobs to run.
     * @param int $maxRuntime The maximum number of seconds to run.
     * @return int|null The exit code.
     */
    public function run(Container $container, Console $io, string $config, string $queue, int $maxJobs, int $maxRuntime): int|null
    {
        $pid = pcntl_fork();

        if ($pid === -1) {
            throw new RuntimeException('Unable to fork process');
        }

        if ($pid) {
            $io->write('Worker started on PID: '.$pid, [
                'color' => Console::CYAN,
            ]);
        } else {
            $worker = $container->build(Worker::class, [
                'options' => [
                    'config' => $config,
                    'queue' => $queue,
                    'maxJobs' => $maxJobs,
                    'maxRuntime' => $maxRuntime,
                ],
            ]);
            $worker->run();
        }

        return static::CODE_SUCCESS;
    }
}
