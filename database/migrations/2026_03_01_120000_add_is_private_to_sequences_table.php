<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sequences', function (Blueprint $table): void {
            $table->boolean('is_private')->default(true)->after('fasta_path');
        });
    }

    public function down(): void
    {
        Schema::table('sequences', function (Blueprint $table): void {
            $table->dropColumn('is_private');
        });
    }
};
