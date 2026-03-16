<?php

namespace AlfonsoBries\Geo\Commands;

use AlfonsoBries\Geo\Sync\GeoSyncManager;
use Illuminate\Console\Command;

class GeoSyncCommand extends Command
{
    protected $signature = 'geo:sync
        {--force : Force full re-sync even if checksums match}
        {--tables= : Comma-separated list of tables to sync}';

    protected $description = 'Sync geo data from geo.vexilo.com';

    public function handle(GeoSyncManager $manager): int
    {
        $tables = $this->option('tables')
            ? explode(',', $this->option('tables'))
            : null;

        $force = (bool) $this->option('force');

        $this->info('Starting geo sync...');

        try {
            $manager->sync($tables, $force, function (string $table, string $status, int $count = 0): void {
                match ($status) {
                    'skipped' => $this->line("  <comment>{$table}</comment>: up to date, skipped"),
                    'syncing' => $this->line("  <info>{$table}</info>: syncing..."),
                    'page' => $this->line("    synced {$count} records"),
                    'completed' => $this->line("  <info>{$table}</info>: completed"),
                };
            });

            $this->info('Geo sync completed successfully.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Sync failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
