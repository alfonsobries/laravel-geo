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
            ->timeout(60);
    }

    /**
     * @return array<string, array{checksum: string, record_count: int, last_synced_at: string}>
     */
    public function getManifest(): array
    {
        return $this->http->get('/manifest')->throw()->json('data');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getContinents(): array
    {
        return $this->http->get('/continents')->throw()->json('data');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCountries(): array
    {
        return $this->http->get('/countries')->throw()->json('data');
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, next_cursor: string|null, has_more: bool}
     */
    public function getDivisions(?string $cursor = null): array
    {
        return $this->http->get('/divisions', $cursor ? ['cursor' => $cursor] : [])->throw()->json();
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, next_cursor: string|null, has_more: bool}
     */
    public function getCities(?string $cursor = null): array
    {
        return $this->http->get('/cities', $cursor ? ['cursor' => $cursor] : [])->throw()->json();
    }

    public function getMaxmindChecksum(): ?string
    {
        return $this->http->get('/maxmind/checksum')->throw()->json('checksum');
    }

    public function downloadMaxmind(string $destination): void
    {
        $response = $this->http->withOptions([
            'sink' => $destination,
            'timeout' => 300,
        ])->get('/maxmind/download')->throw();
    }
}
