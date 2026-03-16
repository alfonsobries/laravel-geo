<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('iso', 3)->unique();
            $table->string('iso_numeric', 3)->unique();
            $table->string('name');
            $table->string('name_official')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('timezone_id', 32)->nullable()->index();
            $table->foreignId('continent_id')->constrained()->cascadeOnDelete();
            $table->string('capital')->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->string('currency_name')->nullable();
            $table->string('tld', 10)->nullable();
            $table->string('phone_code', 20)->nullable();
            $table->string('postal_code_format')->nullable();
            $table->string('postal_code_regex')->nullable();
            $table->string('languages')->nullable();
            $table->string('neighbours')->nullable();
            $table->float('area')->unsigned()->nullable();
            $table->string('fips', 2)->nullable();
            $table->unsignedBigInteger('population')->nullable();
            $table->unsignedInteger('elevation')->nullable();
            $table->smallInteger('dem')->nullable();
            $table->string('feature_code', 10)->nullable();
            $table->unsignedInteger('geoname_id')->unique()->nullable();
            $table->timestamps();

            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
