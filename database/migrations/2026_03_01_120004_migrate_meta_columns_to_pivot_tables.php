<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $metaAnimals = DB::table('meta_animals')
                ->select('id', 'risk_factors_id', 'clinical_signs_id', 'lesions_id')
                ->get();
            foreach ($metaAnimals as $row) {
                if (! empty($row->risk_factors_id)) {
                    DB::table('risk_factors_meta')->insertOrIgnore([
                        'meta_type' => 'App\Models\MetaAnimal',
                        'meta_id' => $row->id,
                        'risk_factors_id' => $row->risk_factors_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                if (! empty($row->clinical_signs_id)) {
                    DB::table('signs_meta')->insertOrIgnore([
                        'meta_type' => 'App\Models\MetaAnimal',
                        'meta_id' => $row->id,
                        'clinical_signs_id' => $row->clinical_signs_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                if (! empty($row->lesions_id)) {
                    DB::table('lesions_meta')->insertOrIgnore([
                        'meta_type' => 'App\Models\MetaAnimal',
                        'meta_id' => $row->id,
                        'lesions_id' => $row->lesions_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $metaHumans = DB::table('meta_humans')
                ->select('id', 'risk_factors_id', 'clinical_signs_id', 'lesions_id')
                ->get();
            foreach ($metaHumans as $row) {
                if (! empty($row->risk_factors_id)) {
                    DB::table('risk_factors_meta')->insertOrIgnore([
                        'meta_type' => 'App\Models\MetaHuman',
                        'meta_id' => $row->id,
                        'risk_factors_id' => $row->risk_factors_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                if (! empty($row->clinical_signs_id)) {
                    DB::table('signs_meta')->insertOrIgnore([
                        'meta_type' => 'App\Models\MetaHuman',
                        'meta_id' => $row->id,
                        'clinical_signs_id' => $row->clinical_signs_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                if (! empty($row->lesions_id)) {
                    DB::table('lesions_meta')->insertOrIgnore([
                        'meta_type' => 'App\Models\MetaHuman',
                        'meta_id' => $row->id,
                        'lesions_id' => $row->lesions_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $metaParasites = DB::table('meta_parasites')->select('id', 'risk_factors_id')->get();
            foreach ($metaParasites as $row) {
                if (! empty($row->risk_factors_id)) {
                    DB::table('risk_factors_meta')->insertOrIgnore([
                        'meta_type' => 'App\Models\MetaParasite',
                        'meta_id' => $row->id,
                        'risk_factors_id' => $row->risk_factors_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $metaEnvironments = DB::table('meta_environments')->select('id', 'risk_factors_id')->get();
            foreach ($metaEnvironments as $row) {
                if (! empty($row->risk_factors_id)) {
                    DB::table('risk_factors_meta')->insertOrIgnore([
                        'meta_type' => 'App\Models\MetaEnvironment',
                        'meta_id' => $row->id,
                        'risk_factors_id' => $row->risk_factors_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

    }

    public function down(): void
    {
        // Keep legacy columns intact; remove migrated pivot associations.
        DB::table('signs_meta')->whereIn('meta_type', [
            'App\Models\MetaAnimal',
            'App\Models\MetaHuman',
        ])->delete();

        DB::table('lesions_meta')->whereIn('meta_type', [
            'App\Models\MetaAnimal',
            'App\Models\MetaHuman',
        ])->delete();

        DB::table('risk_factors_meta')->whereIn('meta_type', [
            'App\Models\MetaAnimal',
            'App\Models\MetaHuman',
            'App\Models\MetaParasite',
            'App\Models\MetaEnvironment',
        ])->delete();
    }
};
