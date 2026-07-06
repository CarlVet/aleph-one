<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tube_requests', function (Blueprint $table) {
            $table->dropForeign(['principal_investigator_id']);
            $table->dropColumn('principal_investigator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tube_requests', function (Blueprint $table) {
            $table->foreignId('principal_investigator_id')->constrained('people')->onDelete('cascade');
        });
    }
};
