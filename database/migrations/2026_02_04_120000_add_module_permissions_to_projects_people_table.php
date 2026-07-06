<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects_people', function (Blueprint $table): void {
            if (! Schema::hasColumn('projects_people', 'module_permissions')) {
                $table->json('module_permissions')->nullable()->after('permission');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects_people', function (Blueprint $table): void {
            if (Schema::hasColumn('projects_people', 'module_permissions')) {
                $table->dropColumn('module_permissions');
            }
        });
    }
};
