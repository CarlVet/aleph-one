<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_projects', function (Blueprint $table) {
            $table->string('title')->nullable()->after('name');
            $table->date('date_started')->nullable()->after('status');
            $table->date('date_end_intended')->nullable()->after('date_started');
            $table->date('date_end')->nullable()->after('date_end_intended');
        });
    }

    public function down(): void
    {
        Schema::table('sub_projects', function (Blueprint $table) {
            $table->dropColumn([
                'title',
                'date_started',
                'date_end_intended',
                'date_end',
            ]);
        });
    }
};
