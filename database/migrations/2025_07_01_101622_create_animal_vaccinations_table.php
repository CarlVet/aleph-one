<?php

use App\Models\Animals;
use App\Models\People;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('animal_vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Animals::class)->constrained()->cascadeOnDelete();
            $table->string('vaccine_name');
            $table->string('vaccine_type')->nullable(); // e.g., 'Core', 'Non-core', 'Optional'
            $table->date('date_administered');
            $table->date('next_due_date')->nullable();
            $table->foreignIdFor(People::class, 'administered_by')->nullable()->constrained()->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animal_vaccinations');
    }
};
