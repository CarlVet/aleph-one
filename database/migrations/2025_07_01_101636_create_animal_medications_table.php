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
        Schema::create('animal_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Animals::class)->constrained()->cascadeOnDelete();
            $table->string('medication_name');
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable(); // e.g., 'daily', 'twice daily', 'as needed'
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->foreignIdFor(People::class, 'prescribed_by')->nullable()->constrained()->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animal_medications');
    }
};
