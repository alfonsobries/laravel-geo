<?php

namespace AlfonsoBries\Geo\Sync;

use Illuminate\Database\Eloquent\Model;

class SyncManifest extends Model
{
    protected $table = 'geo_sync_state';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'record_count' => 'integer',
            'completed' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }
}
