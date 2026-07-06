<?php

use App\Models\Humans;
use App\Models\Locations;
use App\Models\People;
use App\Models\Projects;
use App\Models\SampleTypes;
use App\Models\SamplingSites;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('human_samples', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignIdFor(Humans::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(SampleTypes::class)->constrained()->cascadeOnDelete();
            $table->date('date_collected');
            $table->foreignIdFor(People::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(SamplingSites::class)->nullable()->constrained()->cascadeOnDelete();
            $table->string('area')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 10, 8)->nullable();
            $table->string('sample_purpose')->nullable(); // e.g., 'diagnostic', 'research', 'surveillance
            $table->foreignIdFor(Locations::class)->constrained()->cascadeOnDelete();
            $table->string('storage_state')->nullable();
            $table->boolean('processed')->nullable();
            $table->foreignIdFor(Projects::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('human_samples');
    }
};
