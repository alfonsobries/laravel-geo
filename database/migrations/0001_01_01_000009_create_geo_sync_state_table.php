<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_sync_state', function (Blueprint $table) {
            $table->id();
            $table->string('table_name')->unique();
            $table->string('checksum', 32)->nullable();
            $table->unsignedInteger('record_count')->default(0);
            $table->string('last_cursor')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_sync_state');
    }
};
