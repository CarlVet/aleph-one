<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_project_people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_project_id')->constrained('sub_projects')->cascadeOnDelete();
            $table->foreignId('people_id')->constrained('people')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['sub_project_id', 'people_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_project_people');
    }
};
