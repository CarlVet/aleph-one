<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parasite_samples', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('date_processed');
        });
    }

    public function down(): void
    {
        Schema::table('parasite_samples', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
