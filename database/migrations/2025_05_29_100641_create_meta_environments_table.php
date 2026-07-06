<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_environments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studies_id')->constrained('studies')->onDelete('cascade');
            $table->foreignId('environment_sample_types_id')->constrained('environment_sample_types')->onDelete('cascade');
            $table->string('location')->nullable();
            $table->foreignId('countries_id')->constrained('countries')->onDelete('cascade');
            $table->string('date_sampling')->nullable();
            $table->foreignId('pathogens_id')->constrained('pathogens')->onDelete('cascade');
            $table->foreignId('techniques_id')->constrained('techniques')->onDelete('cascade');
            $table->integer('tested_n');
            $table->integer('pos_n');
            $table->foreignId('risk_factors_id')->constrained('risk_factors')->onDelete('cascade');
            $table->foreignId('projects_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('people_id')->constrained('people')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_environments');
    }
};
