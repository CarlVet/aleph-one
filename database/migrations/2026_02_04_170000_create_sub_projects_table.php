<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['project_id', 'code']);
            $table->unique(['project_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_projects');
    }
};
