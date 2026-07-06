<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('ethics_ref')->nullable();
            $table->date('date_started')->nullable();
            $table->date('date_end_intended')->nullable();
            $table->date('date_end')->nullable();
            $table->string('status')->default('active');
            $table->text('objectives')->nullable();
            $table->text('methodology')->nullable();
            $table->text('expected_outcomes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
