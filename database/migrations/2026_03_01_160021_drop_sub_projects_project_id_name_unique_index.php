<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_projects', function (Blueprint $table) {
            $table->dropUnique('sub_projects_project_id_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sub_projects', function (Blueprint $table) {
            $table->unique(['project_id', 'name']);
        });
    }
};
