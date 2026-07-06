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
        Schema::table('animal_health', function (Blueprint $table) {
            $table->dropColumn(['vaccination_status', 'medical_history']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('animal_health', function (Blueprint $table) {
            $table->string('vaccination_status')->nullable()->after('health_status');
            $table->text('medical_history')->nullable()->after('alive');
        });
    }
};
