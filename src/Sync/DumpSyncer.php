<?php

namespace AlfonsoBries\Geo\Sync;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DumpSyncer
{
    /**
     * @var array<string, array{table: string, translation_table: string|null, parent_fk: string|null, foreign_keys: array<string, string>, unique_key: string}>
     */
    private const array TABLE_CONFIG = [
        'continents' => ['table' => 'continents', 'foreign_keys' => [], 'unique_key' => 'geoname_id'],
        'continent_translations' => ['table' => 'continent_translations', 'foreign_keys' => ['continent_geoname_id' => 'continents'], 'unique_key' => 'alternate_name_id'],
        'countries' => ['table' => 'countries', 'foreign_keys' => ['continent_geoname_id' => 'continents'], 'unique_key' => 'geoname_id'],
        'country_translations' => ['table' => 'country_translations', 'foreign_keys' => ['country_geoname_id' => 'countries'], 'unique_key' => 'alternate_name_id'],
        'divisions' => ['table' => 'divisions', 'foreign_keys' => ['country_geoname_id' => 'countries'], 'unique_key' => 'geoname_id'],
        'division_translations' => ['table' => 'division_translations', 'foreign_keys' => ['division_geoname_id' => 'divisions'], 'unique_key' => 'alternate_name_id'],
        'cities' => ['table' => 'cities', 'foreign_keys' => ['country_geoname_id' => 'countries', 'division_geoname_id' => 'divisions'], 'unique_key' => 'geoname_id'],
        'city_translations' => ['table' => 'city_translations', 'foreign_keys' => ['city_geoname_id' => 'cities'], 'unique_key' => 'alternate_name_id'],
    ];

    /**
     * @var array<string, array<int, int>>
     */
    private array $lookupMaps = [];

    public function __construct(
        private readonly GeoApiClient $client,
    ) {}

    /**
     * Sync a table from a CSV dump file.
     */
    public function syncFromDump(string $tableName, ?\Closure $onProgress = null): void
    {
        $config = self::TABLE_CONFIG[$tableName] ?? null;

        if (! $config) {
            return;
        }

        // Build FK lookup maps for dependencies
        foreach ($config['foreign_keys'] as $sourceTable) {
            if (! isset($this->lookupMaps[$sourceTable])) {
                $this->buildLookupMap($sourceTable);
            }
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'geo_dump_');

        try {
            $this->client->downloadDump($tableName, $tempFile);

            $this->importCsv($tableName, $config, $tempFile, $onProgress);

            // Rebuild lookup map after import (needed for dependent tables)
            if (in_array($tableName, ['continents', 'countries', 'divisions', 'cities'])) {
                $this->buildLookupMap($config['table']);
            }
        } finally {
            @unlink($tempFile);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function importCsv(string $tableName, array $config, string $filePath, ?\Closure $onProgress): void
    {
        $table = $config['table'];
        $foreignKeys = $config['foreign_keys'];
        $uniqueKey = $config['unique_key'];

        $gz = gzopen($filePath, 'rb');

        if (! $gz) {
            throw new \RuntimeException("Failed to open dump file for {$tableName}");
        }

        // Read header
        $headerLine = gzgets($gz);
        $headers = str_getcsv(trim($headerLine));

        // Map geoname_id FK columns to local FK columns
        $fkMapping = [];
        foreach ($foreignKeys as $geonameCol => $sourceTable) {
            $localCol = str_replace('_geoname_id', '_id', $geonameCol);
            $fkMapping[$geonameCol] = ['local_col' => $localCol, 'source_table' => $sourceTable];
        }

        // Delete existing data (truncate + reimport is faster and simpler)
        DB::table($table)->delete();

        $batch = [];
        $batchSize = 2000;
        $totalImported = 0;

        while (! gzeof($gz)) {
            $line = gzgets($gz);

            if ($line === false || trim($line) === '') {
                continue;
            }

            $values = str_getcsv(trim($line));

            if (count($values) !== count($headers)) {
                continue;
            }

            $row = array_combine($headers, $values);

            // Convert empty strings to null
            foreach ($row as $key => $value) {
                if ($value === '') {
                    $row[$key] = null;
                }
            }

            // Resolve foreign keys
            foreach ($fkMapping as $geonameCol => $mapping) {
                $geonameId = $row[$geonameCol] ?? null;

                if ($geonameId !== null) {
                    $map = $this->lookupMaps[$mapping['source_table']] ?? [];
                    $row[$mapping['local_col']] = $map[(int) $geonameId] ?? null;
                } else {
                    $row[$mapping['local_col']] = null;
                }

                unset($row[$geonameCol]);
            }

            // Remove the 'id' column (let DB auto-increment)
            unset($row['id']);

            // Parse dates
            if (isset($row['created_at'])) {
                $row['created_at'] = $this->parseDate($row['created_at']);
            }
            if (isset($row['updated_at'])) {
                $row['updated_at'] = $this->parseDate($row['updated_at']);
            }

            $batch[] = $row;

            if (count($batch) >= $batchSize) {
                DB::table($table)->insert($batch);
                $totalImported += count($batch);

                if ($onProgress) {
                    $onProgress($tableName, 'page', $totalImported);
                }

                $batch = [];
            }
        }

        // Insert remaining
        if (! empty($batch)) {
            DB::table($table)->insert($batch);
            $totalImported += count($batch);
        }

        gzclose($gz);
    }

    /**
     * @return array<int, int>
     */
    private function buildLookupMap(string $table): array
    {
        $map = [];

        DB::table($table)
            ->select(['id', 'geoname_id'])
            ->whereNotNull('geoname_id')
            ->orderBy('id')
            ->chunk(5000, function ($rows) use (&$map): void {
                foreach ($rows as $row) {
                    $map[$row->geoname_id] = $row->id;
                }
            });

        $this->lookupMaps[$table] = $map;

        return $map;
    }

    private function parseDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        return Carbon::parse($date)->toDateTimeString();
    }

    /**
     * @return array<string>
     */
    public static function allTables(): array
    {
        return array_keys(self::TABLE_CONFIG);
    }

    /**
     * @return array<string>
     */
    public static function mainTables(): array
    {
        return ['continents', 'countries', 'divisions', 'cities'];
    }
}
