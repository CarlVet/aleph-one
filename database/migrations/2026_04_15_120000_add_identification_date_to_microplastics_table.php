<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('microplastics', function (Blueprint $table) {
            $table->date('identification_date')->nullable()->after('m_feret');
        });

        DB::table('microplastics')
            ->whereNull('identification_date')
            ->update([
                'identification_date' => DB::raw('date(created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('microplastics', function (Blueprint $table) {
            $table->dropColumn('identification_date');
        });
    }
};
