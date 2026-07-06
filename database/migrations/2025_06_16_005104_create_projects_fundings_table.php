<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects_fundings', function (Blueprint $table) {
            $table->primary(['fundings_id', 'projects_id']);
            $table->foreignId('fundings_id')->constrained('fundings')->onDelete('cascade');
            $table->foreignId('projects_id')->constrained('projects')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects_fundings');
    }
};
