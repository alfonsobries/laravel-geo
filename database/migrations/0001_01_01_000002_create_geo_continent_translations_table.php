<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('continent_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('continent_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_preferred')->default(false);
            $table->boolean('is_short')->default(false);
            $table->boolean('is_colloquial')->default(false);
            $table->boolean('is_historic')->default(false);
            $table->string('locale', 24)->nullable();
            $table->unsignedInteger('alternate_name_id')->unique();
            $table->timestamps();

            $table->index(['continent_id', 'locale']);
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('continent_translations');
    }
};
