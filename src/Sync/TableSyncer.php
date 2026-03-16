<?php

namespace AlfonsoBries\Geo\Sync;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TableSyncer
{
    /**
     * @var array<string, array<int, int>>
     */
    private array $geonameIdMaps = [];

    /**
     * @param array<int, int> $map
     */
    public function setGeonameIdMap(string $table, array $map): void
    {
        $this->geonameIdMaps[$table] = $map;
    }

    /**
     * @return array<int, int>
     */
    public function getGeonameIdMap(string $table): array
    {
        return $this->geonameIdMaps[$table] ?? [];
    }

    /**
     * Build geoname_id -> local id lookup map for a table.
     *
     * @return array<int, int>
     */
    public function buildLookupMap(string $table): array
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

        $this->geonameIdMaps[$table] = $map;

        return $map;
    }

    /**
     * Upsert a batch of records from the API into a geo table.
     *
     * @param array<int, array<string, mixed>> $records
     * @param array<string, string> $foreignKeyMap Maps API field -> local table for FK resolution
     */
    public function upsertRecords(string $table, array $records, string $uniqueKey = 'geoname_id', array $foreignKeyMap = []): void
    {
        if (empty($records)) {
            return;
        }

        $rows = [];
        $columns = Schema::getColumnListing($table);

        foreach ($records as $record) {
            $row = [];

            // Resolve foreign keys from geoname_id references
            foreach ($foreignKeyMap as $apiField => $sourceTable) {
                $localField = str_replace('_geoname_id', '_id', $apiField);
                $geonameId = $record[$apiField] ?? null;

                if ($geonameId !== null) {
                    $map = $this->getGeonameIdMap($sourceTable);
                    $row[$localField] = $map[$geonameId] ?? null;
                } else {
                    $row[$localField] = null;
                }

                unset($record[$apiField]);
            }

            // Remove nested relationships (translations handled separately)
            unset($record['translations']);

            // Map remaining fields
            foreach ($record as $key => $value) {
                if (in_array($key, $columns)) {
                    $row[$key] = $value;
                }
            }

            $now = now()->toDateTimeString();
            $row['created_at'] = isset($row['created_at']) ? $this->parseDate($row['created_at']) : $now;
            $row['updated_at'] = isset($row['updated_at']) ? $this->parseDate($row['updated_at']) : $now;

            $rows[] = $row;
        }

        $updateColumns = array_values(array_diff(array_keys($rows[0]), ['id', $uniqueKey, 'created_at']));

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($table)->upsert($chunk, [$uniqueKey], $updateColumns);
        }
    }

    /**
     * Upsert translation records.
     *
     * @param array<int, array<string, mixed>> $records Records from API with translations nested
     * @param string $parentTable Parent table name (e.g., 'continents')
     */
    public function upsertTranslations(string $translationTable, string $parentTable, array $records, string $parentForeignKey): void
    {
        $rows = [];
        $parentMap = $this->getGeonameIdMap($parentTable);

        foreach ($records as $record) {
            $parentGeonameId = $record['geoname_id'] ?? null;
            $parentLocalId = $parentMap[$parentGeonameId] ?? null;

            if (! $parentLocalId || empty($record['translations'])) {
                continue;
            }

            foreach ($record['translations'] as $translation) {
                $rows[] = [
                    $parentForeignKey => $parentLocalId,
                    'name' => $translation['name'],
                    'locale' => $translation['locale'] ?? null,
                    'is_preferred' => $translation['is_preferred'] ?? false,
                    'is_short' => $translation['is_short'] ?? false,
                    'is_colloquial' => $translation['is_colloquial'] ?? false,
                    'is_historic' => $translation['is_historic'] ?? false,
                    'alternate_name_id' => $translation['alternate_name_id'],
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ];
            }
        }

        if (empty($rows)) {
            return;
        }

        $updateColumns = ['name', 'locale', 'is_preferred', 'is_short', 'is_colloquial', 'is_historic', 'updated_at'];

        // Batch upserts to avoid MySQL placeholder limit (65535)
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($translationTable)->upsert($chunk, ['alternate_name_id'], $updateColumns);
        }
    }

    /**
     * Delete records not seen during sync using a tracker table.
     *
     * @param array<int, int> $seenGeonameIds
     */
    public function deleteUnseen(string $table, array $seenGeonameIds): int
    {
        if (empty($seenGeonameIds)) {
            return 0;
        }

        return DB::table($table)
            ->whereNotNull('geoname_id')
            ->whereNotIn('geoname_id', $seenGeonameIds)
            ->delete();
    }

    private function parseDate(string $date): string
    {
        return \Illuminate\Support\Carbon::parse($date)->toDateTimeString();
    }
}
