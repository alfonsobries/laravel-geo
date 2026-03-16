<?php

namespace AlfonsoBries\Geo\Commands;

use AlfonsoBries\Geo\Sync\GeoSyncManager;
use Illuminate\Console\Command;

class GeoStatusCommand extends Command
{
    protected $signature = 'geo:status';

    protected $description = 'Show geo sync status vs remote';

    public function handle(GeoSyncManager $manager): int
    {
        try {
            $status = $manager->getStatus();

            $rows = [];
            foreach ($status as $table => $info) {
                $rows[] = [
                    $table,
                    $info['local_count'],
                    $info['remote_count'],
                    $info['in_sync'] ? 'Yes' : 'No',
                    $info['last_synced_at'] ?? 'Never',
                ];
            }

            $this->table(
                ['Table', 'Local Count', 'Remote Count', 'In Sync', 'Last Synced'],
                $rows,
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed to get status: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
