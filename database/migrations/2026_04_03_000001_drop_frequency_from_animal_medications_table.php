<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animal_medications', function (Blueprint $table) {
            $table->dropColumn('frequency');
        });
    }

    public function down(): void
    {
        Schema::table('animal_medications', function (Blueprint $table) {
            $table->string('frequency')->nullable()->after('dosage');
        });
    }
};
