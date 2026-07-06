<?php

use App\Models\Animals;
use App\Models\ClinicalSigns;
use App\Models\Lesions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_health', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Animals::class)->constrained()->cascadeOnDelete();
            $table->string('health_status')->nullable(); // e.g., 'healthy', 'sick', 'recovered'
            $table->date('check_date')->nullable();
            $table->string('check_type')->nullable(); // e.g., 'routine', 'follow-up', 'emergency', 'treatment
            $table->foreignIdFor(ClinicalSigns::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Lesions::class)->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('alive')->default(true); // Indicates if the animal is alive
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_health');
    }
};
