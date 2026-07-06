<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sample_types', function (Blueprint $table) {
            $table->enum('category', ['host_derived', 'non_host_derived'])->default('host_derived');
        });

        DB::table('sample_types')
            ->where('name', 'Parasites')
            ->update(['category' => 'non_host_derived']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sample_types', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
