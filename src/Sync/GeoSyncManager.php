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
        private readonly TableSyncer $syncer,
    ) {}

    /**
     * @param array<string>|null $tables
     */
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
     * @param array<string>|null $tables
     */
    public function sync(?array $tables = null, bool $force = false, ?\Closure $onProgress = null): void
    {
        $manifest = $this->client->getManifest();
        $enabledTables = $this->getEnabledTables($tables);

        foreach ($enabledTables as $tableName) {
            $config = self::TABLE_CONFIG[$tableName];

            if (! $force && $this->isUpToDate($tableName, $manifest)) {
                if ($onProgress) {
                    $onProgress($tableName, 'skipped');
                }

                continue;
            }

            if ($onProgress) {
                $onProgress($tableName, 'syncing');
            }

            // Build lookup maps for foreign key resolution
            foreach ($config['foreign_keys'] as $sourceTable) {
                if (empty($this->syncer->getGeonameIdMap($sourceTable))) {
                    $this->syncer->buildLookupMap($sourceTable);
                }
            }

            if ($config['paginated']) {
                $this->syncPaginated($tableName, $config, $manifest, $onProgress);
            } else {
                $this->syncFull($tableName, $config, $manifest, $onProgress);
            }

            if ($onProgress) {
                $onProgress($tableName, 'completed');
            }
        }
    }

    /**
     * @return array<string, array{local_checksum: string|null, remote_checksum: string, record_count: int, in_sync: bool, last_synced_at: string|null}>
     */
    public function getStatus(): array
    {
        $manifest = $this->client->getManifest();
        $status = [];

        foreach (self::TABLE_CONFIG as $tableName => $config) {
            $state = SyncManifest::query()->where('table_name', $tableName)->first();

            $remoteChecksum = $manifest[$tableName]['checksum'] ?? null;
            $localChecksum = $state?->checksum;

            $status[$tableName] = [
                'local_checksum' => $localChecksum,
                'remote_checksum' => $remoteChecksum,
                'local_count' => DB::table($config['table'])->count(),
                'remote_count' => $manifest[$tableName]['record_count'] ?? 0,
                'in_sync' => $localChecksum === $remoteChecksum,
                'last_synced_at' => $state?->last_synced_at?->toIso8601String(),
            ];
        }

        return $status;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, array<string, mixed>> $manifest
     */
    private function syncFull(string $tableName, array $config, array $manifest, ?\Closure $onProgress): void
    {
        $method = $config['fetch_method'];
        $records = $this->client->{$method}();

        DB::transaction(function () use ($tableName, $config, $records): void {
            $this->syncer->upsertRecords(
                $config['table'],
                $records,
                'geoname_id',
                $config['foreign_keys'],
            );

            // Rebuild lookup map after upsert
            $this->syncer->buildLookupMap($config['table']);

            // Upsert translations
            $this->syncer->upsertTranslations(
                $config['translation_table'],
                $config['table'],
                $records,
                $config['parent_fk'],
            );

            // Delete records not present in remote
            $seenGeonameIds = array_column($records, 'geoname_id');
            $this->syncer->deleteUnseen($config['table'], $seenGeonameIds);
        });

        $this->updateSyncState($tableName, $manifest);
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, array<string, mixed>> $manifest
     */
    private function syncPaginated(string $tableName, array $config, array $manifest, ?\Closure $onProgress): void
    {
        $state = SyncManifest::query()->where('table_name', $tableName)->first();
        $cursor = ($state && ! $state->completed) ? $state->last_cursor : null;
        $seenGeonameIds = [];
        $method = $config['fetch_method'];

        // If resuming, collect already-synced geoname_ids
        if ($cursor) {
            $seenGeonameIds = DB::table($config['table'])
                ->whereNotNull('geoname_id')
                ->pluck('geoname_id')
                ->all();
        }

        do {
            $response = $this->client->{$method}($cursor);
            $records = $response['data'];
            $cursor = $response['next_cursor'];
            $hasMore = $response['has_more'];

            DB::transaction(function () use ($config, $records, &$seenGeonameIds): void {
                $this->syncer->upsertRecords(
                    $config['table'],
                    $records,
                    'geoname_id',
                    $config['foreign_keys'],
                );

                // Rebuild lookup map after each page
                $this->syncer->buildLookupMap($config['table']);

                $this->syncer->upsertTranslations(
                    $config['translation_table'],
                    $config['table'],
                    $records,
                    $config['parent_fk'],
                );
            });

            // Track seen geoname_ids
            foreach ($records as $record) {
                if ($record['geoname_id'] ?? null) {
                    $seenGeonameIds[] = $record['geoname_id'];
                }
            }

            // Save cursor for resumability
            SyncManifest::updateOrCreate(
                ['table_name' => $tableName],
                [
                    'last_cursor' => $cursor,
                    'completed' => ! $hasMore,
                ],
            );

            if ($onProgress) {
                $onProgress($tableName, 'page', count($records));
            }
        } while ($hasMore);

        // Delete unseen records after full sync
        $this->syncer->deleteUnseen($config['table'], $seenGeonameIds);

        $this->updateSyncState($tableName, $manifest);
    }

    /**
     * @param array<string, array<string, mixed>> $manifest
     */
    private function updateSyncState(string $tableName, array $manifest): void
    {
        $tableManifest = $manifest[$tableName] ?? [];
        $translationKey = str_replace('ies', 'y', rtrim($tableName, 's')).'_translations';

        // Also store translation table checksum
        SyncManifest::updateOrCreate(
            ['table_name' => $tableName],
            [
                'checksum' => $tableManifest['checksum'] ?? null,
                'record_count' => $tableManifest['record_count'] ?? 0,
                'completed' => true,
                'last_cursor' => null,
                'last_synced_at' => now(),
            ],
        );
    }

    /**
     * @param array<string, array<string, mixed>> $manifest
     */
    private function isUpToDate(string $tableName, array $manifest): bool
    {
        $state = SyncManifest::query()->where('table_name', $tableName)->first();

        if (! $state || ! $state->completed) {
            return false;
        }

        $remoteChecksum = $manifest[$tableName]['checksum'] ?? null;

        return $state->checksum === $remoteChecksum;
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
}
