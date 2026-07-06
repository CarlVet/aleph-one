<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('humans', function (Blueprint $table) {
            $table->foreignId('people_id')->nullable()->after('projects_id')->constrained()->nullOnDelete();
        });

        // Backfill the registrar for existing patients from their earliest human sample.
        $owners = DB::table('human_samples')
            ->select('humans_id', DB::raw('MIN(people_id) as people_id'))
            ->whereNotNull('people_id')
            ->groupBy('humans_id')
            ->get();

        foreach ($owners as $owner) {
            DB::table('humans')
                ->where('id', $owner->humans_id)
                ->whereNull('people_id')
                ->update(['people_id' => $owner->people_id]);
        }
    }

    public function down(): void
    {
        Schema::table('humans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('people_id');
        });
    }
};
