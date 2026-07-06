<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boxes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->unique()->nullable();
            $table->string('content_type')->nullable();
            $table->string('content_state')->nullable();
            $table->integer('n_rows');
            $table->integer('n_columns');
            $table->foreignId('projects_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boxes');
    }
};
