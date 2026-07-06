<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parasite_observation_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parasite_observations_id')->constrained('parasite_observations')->cascadeOnDelete();
            $table->foreignId('users_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('parasite_observation_comments')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index('parasite_observations_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parasite_observation_comments');
    }
};
