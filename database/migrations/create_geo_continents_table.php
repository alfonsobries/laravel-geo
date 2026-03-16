<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('continents', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('name', 50);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('timezone_id', 32)->nullable()->index();
            $table->unsignedBigInteger('population')->default(0);
            $table->smallInteger('dem')->nullable();
            $table->string('feature_code', 10)->nullable();
            $table->unsignedInteger('geoname_id')->unique();
            $table->timestamps();

            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('continents');
    }
};
