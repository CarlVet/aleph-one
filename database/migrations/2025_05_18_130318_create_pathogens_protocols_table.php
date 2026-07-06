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
        Schema::create('pathogens_protocols', function (Blueprint $table) {
            $table->primary(['pathogens_id', 'protocols_id']);
            $table->foreignId('pathogens_id')->constrained('pathogens')->onDelete('cascade');
            $table->foreignId('protocols_id')->constrained('protocols')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pathogens_protocols');
    }
};
