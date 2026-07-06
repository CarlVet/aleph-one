<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parasite_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parasites_id')->constrained('parasites')->cascadeOnDelete();
            $table->foreignId('parasite_observations_id')
                ->nullable()
                ->constrained('parasite_observations')
                ->nullOnDelete();
            $table->string('photo_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parasite_photos');
    }
};
