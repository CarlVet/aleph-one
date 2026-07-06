<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studies', function (Blueprint $table) {
            $table->id();
            $table->string('ref_key')->unique();
            $table->string('title');
            $table->text('abstract')->nullable();
            $table->unsignedSmallInteger('publication_year');
            $table->string('study_design');
            $table->string('pdf_path')->nullable();
            $table->string('risk_bias')->nullable();
            $table->string('sampling_strategy')->nullable();
            $table->string('doi')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studies');
    }
};
