<?php

use App\Models\Locations;
use App\Models\SamplingSites;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environment_samples', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->foreignId('environment_sample_types_id')->constrained('environment_sample_types');
            $table->date('date_collected');
            $table->foreignIdFor(SamplingSites::class)->nullable()->constrained()->cascadeOnDelete();
            $table->string('area')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 10, 8)->nullable();
            $table->foreignIdFor(Locations::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('people_id')->constrained('people');
            $table->foreignId('projects_id')->constrained('projects');
            $table->boolean('processed')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environment_samples');
    }
};
