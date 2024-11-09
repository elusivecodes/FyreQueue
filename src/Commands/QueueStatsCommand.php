<?php
declare(strict_types=1);

namespace Fyre\Queue\Commands;

use Fyre\Command\Command;
use Fyre\Console\Console;
use Fyre\Queue\QueueManager;

use function array_keys;
use function array_map;

/**
 * QueueStatsCommand
 */
class QueueStatsCommand extends Command
{
    protected string|null $alias = 'queue:stats';

    protected string $description = 'Display stats for the queue.';

    protected array $options = [
        'config' => [],
        'queue' => [],
    ];

    /**
     * Run the command.
     *
     * @param QueueManager $queueManager The QueueManager.
     * @param Console $io The Console.
     * @return int|null The exit code.
     */
    public function run(QueueManager $queueManager, Console $io, string|null $config = null, string|null $queue = null): int|null
    {
        $handlers = $queueManager->getConfig();

        foreach ($handlers as $key => $data) {
            if ($config && $key !== $config) {
                continue;
            }

            $instance = $queueManager->use($key);

            $io->write($key, [
                'color' => Console::GREEN,
                'style' => Console::BOLD,
            ]);

            $activeQueues = $instance->queues();

            foreach ($activeQueues as $activeQueue) {
                if ($queue && $activeQueue !== $queue) {
                    continue;
                }

                $stats = $instance->stats($activeQueue);
                $data = array_map(
                    fn(string $key, mixed $value): array => [$key, $value],
                    array_keys($stats),
                    $stats
                );

                $io->write($activeQueue, [
                    'color' => Console::BLUE,
                ]);
                $io->table($data);
            }
        }

        return static::CODE_SUCCESS;
    }
}
