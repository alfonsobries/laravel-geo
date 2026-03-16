<?php

namespace AlfonsoBries\Geo\Sync;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class GeoApiClient
{
    private PendingRequest $http;

    public function __construct()
    {
        $this->http = Http::baseUrl(rtrim(config('geo.api_url'), '/').'/api/v1')
            ->withHeaders(['X-Api-Key' => config('geo.api_key')])
            ->retry(3, 500)
            ->timeout(60);
    }

    /**
     * @return array<string, array{checksum: string, record_count: int, last_synced_at: string|null, dump_checksum: string|null}>
     */
    public function getManifest(): array
    {
        return $this->http->get('/manifest')->throw()->json('data');
    }

    public function hasDumps(): bool
    {
        $manifest = $this->getManifest();

        return ($manifest['continents']['dump_checksum'] ?? null) !== null;
    }

    public function downloadDump(string $table, string $destination): void
    {
        $this->http->withOptions([
            'sink' => $destination,
            'timeout' => 600,
        ])->get("/dumps/{$table}")->throw();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getContinents(?string $updatedAfter = null): array
    {
        $params = $updatedAfter ? ['updated_after' => $updatedAfter] : [];

        return $this->http->get('/continents', $params)->throw()->json('data');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCountries(?string $updatedAfter = null): array
    {
        $params = $updatedAfter ? ['updated_after' => $updatedAfter] : [];

        return $this->http->get('/countries', $params)->throw()->json('data');
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, next_cursor: string|null, has_more: bool}
     */
    public function getDivisions(?string $cursor = null, ?string $updatedAfter = null): array
    {
        $params = array_filter([
            'cursor' => $cursor,
            'updated_after' => $updatedAfter,
        ]);

        return $this->http->get('/divisions', $params)->throw()->json();
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, next_cursor: string|null, has_more: bool}
     */
    public function getCities(?string $cursor = null, ?string $updatedAfter = null): array
    {
        $params = array_filter([
            'cursor' => $cursor,
            'updated_after' => $updatedAfter,
        ]);

        return $this->http->get('/cities', $params)->throw()->json();
    }

    /**
     * @return array<int, array{geoname_id: int, deleted_at: string}>
     */
    public function getDeletions(string $table, string $since): array
    {
        return $this->http->get('/deletions', [
            'table' => $table,
            'since' => $since,
        ])->throw()->json('data');
    }

    public function getMaxmindChecksum(): ?string
    {
        return $this->http->get('/maxmind/checksum')->throw()->json('checksum');
    }

    public function downloadMaxmind(string $destination): void
    {
        $this->http->withOptions([
            'sink' => $destination,
            'timeout' => 300,
        ])->get('/maxmind/download')->throw();
    }
}
