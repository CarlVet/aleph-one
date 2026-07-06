<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->timestamp('starts_at')->nullable()->after('message');
            $table->timestamp('ends_at')->nullable()->after('starts_at');
            $table->string('visibility')->default('all')->after('ends_at'); // all | authenticated | guest
            $table->json('visibility_rules')->nullable()->after('visibility');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['starts_at', 'ends_at', 'visibility', 'visibility_rules']);
        });
    }
};
