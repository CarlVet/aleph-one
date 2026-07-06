<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cultures', function (Blueprint $table) {
            $table->boolean('is_discarded')->default(false)->after('photo_path');
            $table->date('date_discarded')->nullable()->after('is_discarded');
        });
    }

    public function down(): void
    {
        Schema::table('cultures', function (Blueprint $table) {
            $table->dropColumn(['is_discarded', 'date_discarded']);
        });
    }
};
