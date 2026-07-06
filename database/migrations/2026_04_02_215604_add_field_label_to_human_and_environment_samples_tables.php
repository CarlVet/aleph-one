<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('humans', function (Blueprint $table) {
            $table->string('field_label')->nullable()->after('code');
        });

        Schema::table('environment_samples', function (Blueprint $table) {
            $table->string('field_label')->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('humans', function (Blueprint $table) {
            $table->dropColumn('field_label');
        });

        Schema::table('environment_samples', function (Blueprint $table) {
            $table->dropColumn('field_label');
        });
    }
};
