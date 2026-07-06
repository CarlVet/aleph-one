<?php

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
        Schema::create('protocols_studies', function (Blueprint $table) {
            $table->primary(['protocols_id', 'studies_id']);
            $table->foreignId('protocols_id')->constrained('protocols')->onDelete('cascade');
            $table->foreignId('studies_id')->constrained('studies')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('protocols_studies');
    }
};
