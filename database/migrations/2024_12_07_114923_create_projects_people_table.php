<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects_people', function (Blueprint $table) {
            $table->primary(['people_id', 'projects_id']);
            $table->unsignedInteger('people_id');
            $table->unsignedInteger('projects_id');
            $table->foreign('projects_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('people_id')->references('id')->on('people')->onDelete('cascade');
            $table->string('role');
            $table->string('permission');
            $table->date('date_joined')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects_people');
    }
};
