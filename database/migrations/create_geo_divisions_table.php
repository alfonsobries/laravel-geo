<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('timezone_id', 32)->nullable()->index();
            $table->unsignedBigInteger('population')->nullable();
            $table->integer('elevation')->nullable();
            $table->smallInteger('dem')->nullable();
            $table->string('code', 20)->nullable();
            $table->string('feature_code', 10)->nullable();
            $table->unsignedInteger('geoname_id')->unique()->nullable();
            $table->timestamps();

            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('divisions');
    }
};
