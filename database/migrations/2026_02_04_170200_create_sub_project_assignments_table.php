<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_project_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_project_id')->nullable()->constrained('sub_projects')->nullOnDelete();
            $table->morphs('assignable');
            $table->timestamps();

            $table->unique(['assignable_type', 'assignable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_project_assignments');
    }
};
