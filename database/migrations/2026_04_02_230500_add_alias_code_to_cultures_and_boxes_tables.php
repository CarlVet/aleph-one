<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cultures', function (Blueprint $table) {
            $table->string('alias_code')->nullable()->after('code');
        });

        Schema::table('boxes', function (Blueprint $table) {
            $table->string('alias_code')->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('cultures', function (Blueprint $table) {
            $table->dropColumn('alias_code');
        });

        Schema::table('boxes', function (Blueprint $table) {
            $table->dropColumn('alias_code');
        });
    }
};
