<?php

use App\Models\AnimalSpecies;
use App\Models\Projects;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignIdFor(AnimalSpecies::class)->constrained()->cascadeOnDelete();
            $table->string('field_label');
            $table->string('sex');
            $table->string('age');
            $table->morphs('owner');
            $table->foreignIdFor(Projects::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
