<?php

namespace AlfonsoBries\Geo\Sync;

use Illuminate\Support\Facades\DB;

class GeoSyncManager
{
    /**
     * @var array<string, array{table: string, translation_table: string, parent_fk: string, foreign_keys: array<string, string>, paginated: bool, fetch_method: string}>
     */
    private const array TABLE_CONFIG = [
        'continents' => [
            'table' => 'continents',
            'translation_table' => 'continent_translations',
            'parent_fk' => 'continent_id',
            'foreign_keys' => [],
            'paginated' => false,
            'fetch_method' => 'getContinents',
        ],
        'countries' => [
            'table' => 'countries',
            'translation_table' => 'country_translations',
            'parent_fk' => 'country_id',
            'foreign_keys' => ['continent_geoname_id' => 'continents'],
            'paginated' => false,
            'fetch_method' => 'getCountries',
        ],
        'divisions' => [
            'table' => 'divisions',
            'translation_table' => 'division_translations',
            'parent_fk' => 'division_id',
            'foreign_keys' => ['country_geoname_id' => 'countries'],
            'paginated' => true,
            'fetch_method' => 'getDivisions',
        ],
        'cities' => [
            'table' => 'cities',
            'translation_table' => 'city_translations',
            'parent_fk' => 'city_id',
            'foreign_keys' => ['country_geoname_id' => 'countries', 'division_geoname_id' => 'divisions'],
            'paginated' => true,
            'fetch_method' => 'getCities',
        ],
    ];

    public function __construct(
        private readonly GeoApiClient $client,
        private readonly TableSyncer $tableSyncer,
        private readonly DumpSyncer $dumpSyncer,
    ) {}

    /**
     * @param array<string>|null $tables
     */
    public function sync(?array $tables = null, bool $force = false, ?string $mode = 'auto', ?\Closure $onProgress = null): void
    {
        $manifest = $this->client->getManifest();
        $enabledTables = $this->getEnabledTables($tables);
        $resolvedMode = $this->resolveMode($mode, $manifest);

        match ($resolvedMode) {
            'dump' => $this->syncViaDump($enabledTables, $manifest, $onProgress),
            default => $this->syncIncremental($enabledTables, $manifest, $force, $onProgress),
        };
    }

    public function syncMaxmind(bool $force = false, ?\Closure $onProgress = null): bool
    {
        $dbPath = config('geo.maxmind.database_path');

        if (! $force && $dbPath && file_exists($dbPath)) {
            $localChecksum = md5_file($dbPath);
            $remoteChecksum = $this->client->getMaxmindChecksum();

            if ($localChecksum === $remoteChecksum) {
                if ($onProgress) {
                    $onProgress('maxmind', 'skipped');
                }

                return false;
            }
        }

        if ($onProgress) {
            $onProgress('maxmind', 'syncing');
        }

        $targetDir = dirname($dbPath);
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $this->client->downloadMaxmind($dbPath);

        if ($onProgress) {
            $onProgress('maxmind', 'completed');
        }

        return true;
    }

    /**
     * @return array<string, array{local_checksum: string|null, remote_checksum: string|null, local_count: int, remote_count: int, in_sync: bool, last_synced_at: string|null}>
     */
    public function getStatus(): array
    {
        $manifest = $this->client->getManifest();
        $status = [];

        foreach (self::TABLE_CONFIG as $tableName => $config) {
            $state = SyncManifest::query()->where('table_name', $tableName)->first();
            $remoteChecksum = $manifest[$tableName]['checksum'] ?? null;

            $status[$tableName] = [
                'local_checksum' => $state?->checksum,
                'remote_checksum' => $remoteChecksum,
                'local_count' => DB::table($config['table'])->count(),
                'remote_count' => $manifest[$tableName]['record_count'] ?? 0,
                'in_sync' => $state?->checksum === $remoteChecksum,
                'last_synced_at' => $state?->last_synced_at?->toIso8601String(),
            ];
        }

        return $status;
    }

    /**
     * @param array<string, array<string, mixed>> $manifest
     */
    private function resolveMode(?string $mode, array $manifest): string
    {
        if ($mode !== 'auto') {
            return $mode;
        }

        $hasDumps = ($manifest['continents']['dump_checksum'] ?? null) !== null;
        $hasLocalData = DB::table('continents')->exists();

        if ($hasDumps && ! $hasLocalData) {
            return 'dump';
        }

        return 'incremental';
    }

    /**
     * @param array<string> $enabledTables
     * @param array<string, array<string, mixed>> $manifest
     */
    private function syncViaDump(array $enabledTables, array $manifest, ?\Closure $onProgress): void
    {
        foreach ($enabledTables as $tableName) {
            $config = self::TABLE_CONFIG[$tableName];

            // Sync main table
            if ($onProgress) {
                $onProgress($tableName, 'syncing');
            }

            $this->dumpSyncer->syncFromDump($tableName, $onProgress);

            // Sync translation table
            $translationDumpName = $this->translationDumpName($tableName);

            if ($onProgress) {
                $onProgress($translationDumpName, 'syncing');
            }

            $this->dumpSyncer->syncFromDump($translationDumpName, $onProgress);

            if ($onProgress) {
                $onProgress($tableName, 'completed');
            }

            $this->updateSyncState($tableName, $manifest);
        }
    }

    /**
     * @param array<string> $enabledTables
     * @param array<string, array<string, mixed>> $manifest
     */
    private function syncIncremental(array $enabledTables, array $manifest, bool $force, ?\Closure $onProgress): void
    {
        foreach ($enabledTables as $tableName) {
            $config = self::TABLE_CONFIG[$tableName];
            $state = SyncManifest::query()->where('table_name', $tableName)->first();

            if (! $force && $state && $state->completed) {
                $remoteChecksum = $manifest[$tableName]['checksum'] ?? null;
                if ($state->checksum === $remoteChecksum) {
                    if ($onProgress) {
                        $onProgress($tableName, 'skipped');
                    }

                    continue;
                }
            }

            if ($onProgress) {
                $onProgress($tableName, 'syncing');
            }

            foreach ($config['foreign_keys'] as $sourceTable) {
                if (empty($this->tableSyncer->getGeonameIdMap($sourceTable))) {
                    $this->tableSyncer->buildLookupMap($sourceTable);
                }
            }

            $updatedAfter = ($state && $state->completed && ! $force)
                ? $state->last_synced_at?->toIso8601String()
                : null;

            if ($config['paginated']) {
                $this->syncPaginated($tableName, $config, $updatedAfter, $onProgress);
            } else {
                $this->syncFull($tableName, $config, $updatedAfter, $onProgress);
            }

            if ($state && $state->last_synced_at) {
                $this->syncDeletions($tableName, $config['table'], $state->last_synced_at->toIso8601String());
            }

            $this->updateSyncState($tableName, $manifest);

            if ($onProgress) {
                $onProgress($tableName, 'completed');
            }
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function syncFull(string $tableName, array $config, ?string $updatedAfter, ?\Closure $onProgress): void
    {
        $method = $config['fetch_method'];
        $records = $this->client->{$method}($updatedAfter);

        DB::transaction(function () use ($config, $records): void {
            $this->tableSyncer->upsertRecords(
                $config['table'],
                $records,
                'geoname_id',
                $config['foreign_keys'],
            );

            $this->tableSyncer->buildLookupMap($config['table']);

            $this->tableSyncer->upsertTranslations(
                $config['translation_table'],
                $config['table'],
                $records,
                $config['parent_fk'],
            );
        });
    }

    /**
     * @param array<string, mixed> $config
     */
    private function syncPaginated(string $tableName, array $config, ?string $updatedAfter, ?\Closure $onProgress): void
    {
        $method = $config['fetch_method'];
        $cursor = null;

        do {
            $response = $this->client->{$method}($cursor, $updatedAfter);
            $records = $response['data'];
            $cursor = $response['next_cursor'];
            $hasMore = $response['has_more'];

            DB::transaction(function () use ($config, $records): void {
                $this->tableSyncer->upsertRecords(
                    $config['table'],
                    $records,
                    'geoname_id',
                    $config['foreign_keys'],
                );

                $this->tableSyncer->buildLookupMap($config['table']);

                $this->tableSyncer->upsertTranslations(
                    $config['translation_table'],
                    $config['table'],
                    $records,
                    $config['parent_fk'],
                );
            });

            if ($onProgress) {
                $onProgress($tableName, 'page', count($records));
            }
        } while ($hasMore);
    }

    private function syncDeletions(string $tableName, string $table, string $since): void
    {
        try {
            $deletions = $this->client->getDeletions($tableName, $since);

            if (! empty($deletions)) {
                $geonameIds = array_column($deletions, 'geoname_id');
                DB::table($table)->whereIn('geoname_id', $geonameIds)->delete();
            }
        } catch (\Throwable) {
            // Deletions endpoint may not exist on older servers
        }
    }

    /**
     * @param array<string, array<string, mixed>> $manifest
     */
    private function updateSyncState(string $tableName, array $manifest): void
    {
        SyncManifest::updateOrCreate(
            ['table_name' => $tableName],
            [
                'checksum' => $manifest[$tableName]['checksum'] ?? null,
                'record_count' => $manifest[$tableName]['record_count'] ?? 0,
                'completed' => true,
                'last_cursor' => null,
                'last_synced_at' => now(),
            ],
        );
    }

    /**
     * @param array<string>|null $tables
     * @return array<string>
     */
    private function getEnabledTables(?array $tables): array
    {
        $syncConfig = config('geo.sync', []);
        $allTables = array_keys(self::TABLE_CONFIG);

        if ($tables) {
            return array_intersect($tables, $allTables);
        }

        return array_filter($allTables, fn (string $table): bool => $syncConfig[$table] ?? true);
    }

    private function translationDumpName(string $tableName): string
    {
        return match ($tableName) {
            'continents' => 'continent_translations',
            'countries' => 'country_translations',
            'divisions' => 'division_translations',
            'cities' => 'city_translations',
        };
    }
}
