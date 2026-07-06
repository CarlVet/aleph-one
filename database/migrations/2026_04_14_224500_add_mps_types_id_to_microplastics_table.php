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
            $table->foreignId('mps_types_id')->nullable()->after('r_coeff')->constrained('mps_types');
        });

        $existingTypes = DB::table('microplastics')
            ->whereNotNull('mps_type')
            ->where('mps_type', '!=', '')
            ->distinct()
            ->pluck('mps_type');

        foreach ($existingTypes as $typeName) {
            $normalizedName = trim((string) $typeName);
            if ($normalizedName === '') {
                continue;
            }

            DB::table('mps_types')->updateOrInsert(
                ['name' => $normalizedName],
                ['updated_at' => now(), 'created_at' => now()]
            );

            $typeId = DB::table('mps_types')->where('name', $normalizedName)->value('id');

            if ($typeId) {
                DB::table('microplastics')
                    ->where('mps_type', $normalizedName)
                    ->update(['mps_types_id' => $typeId]);
            }
        }

        Schema::table('microplastics', function (Blueprint $table) {
            $table->dropColumn('mps_type');
        });
    }

    public function down(): void
    {
        Schema::table('microplastics', function (Blueprint $table) {
            $table->string('mps_type')->nullable()->after('r_coeff');
        });

        $typeNames = DB::table('microplastics')
            ->leftJoin('mps_types', 'mps_types.id', '=', 'microplastics.mps_types_id')
            ->select('microplastics.id', 'mps_types.name')
            ->get();

        foreach ($typeNames as $record) {
            DB::table('microplastics')
                ->where('id', $record->id)
                ->update(['mps_type' => $record->name]);
        }

        Schema::table('microplastics', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mps_types_id');
        });
    }
};
