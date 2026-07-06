<?php

use App\Models\Laboratories;
use App\Models\ParasiteSpecies;
use App\Models\People;
use App\Models\Projects;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parasites', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignIdFor(ParasiteSpecies::class)->constrained()->cascadeOnDelete();
            $table->string('stage');
            $table->string('sex');
            $table->string('state')->nullable();
            $table->date('date_identified');
            $table->string('photo_path')->nullable();
            $table->morphs('parasites_origin');
            $table->foreignIdFor(Laboratories::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(People::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Projects::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parasites');
    }
};
