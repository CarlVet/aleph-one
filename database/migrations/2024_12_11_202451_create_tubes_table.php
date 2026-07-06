<?php

use App\Models\Projects;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tubes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Primary code (unique, digital ID)
            $table->string('alias_code')->nullable(); // Legacy code (non-unique, physical label)
            $table->morphs('tubes_content');
            $table->string('tube_type')->nullable(); // 1.5ml/2ml tube, 200ul tube, 0.5ml tube, etc.
            $table->string('preservant')->nullable(); // glycerol, formaline, water, ethanol, etc.
            $table->string('purpose')->nullable(); // for DNA extraction, for culture, for direct testing, for long-term storage, etc.
            $table->decimal('amount', 10, 3)->nullable(); // weight, volume or other measurement
            $table->string('amount_unit')->nullable(); // mg, ml, ul, etc.
            $table->date('date_processed')->nullable(); // when the tube was processed
            $table->foreignIdFor(Projects::class)->constrained()->cascadeOnDelete();
            $table->boolean('is_private')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tubes');
    }
};
