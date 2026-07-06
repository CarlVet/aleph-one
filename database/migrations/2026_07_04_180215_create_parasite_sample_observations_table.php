<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parasite_sample_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parasite_samples_id')->constrained('parasite_samples')->cascadeOnDelete();
            $table->date('observed_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('people_id')->nullable()->constrained('people')->nullOnDelete();
            $table->timestamps();

            $table->index(['parasite_samples_id', 'observed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parasite_sample_observations');
    }
};
