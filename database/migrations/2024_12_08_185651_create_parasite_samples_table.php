<?php

use App\Models\Parasites;
use App\Models\ParasiteSampleTypes;
use App\Models\People;
use App\Models\Projects;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parasite_samples', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignIdFor(Parasites::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ParasiteSampleTypes::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(People::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Projects::class)->constrained()->cascadeOnDelete();
            $table->date('date_processed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parasite_samples');
    }
};
