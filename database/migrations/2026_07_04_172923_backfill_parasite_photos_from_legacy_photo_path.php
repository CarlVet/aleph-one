<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->tableExists('parasites')
            || ! $this->tableExists('parasite_observations')
            || ! $this->tableExists('parasite_photos')) {
            return;
        }

        $existingParasiteIds = DB::table('parasite_photos')
            ->select('parasites_id')
            ->distinct()
            ->pluck('parasites_id')
            ->all();

        $legacyParasites = DB::table('parasites')
            ->select('id', 'photo_path', 'people_id', 'created_at', 'updated_at')
            ->whereNotNull('photo_path')
            ->where('photo_path', '<>', '')
            ->when($existingParasiteIds !== [], fn ($query) => $query->whereNotIn('id', $existingParasiteIds))
            ->orderBy('id')
            ->get();

        $now = now();

        foreach ($legacyParasites as $parasite) {
            $observationId = DB::table('parasite_observations')->insertGetId([
                'parasites_id' => $parasite->id,
                'observed_at' => null,
                'notes' => null,
                'people_id' => $parasite->people_id,
                'created_at' => $parasite->created_at ?? $now,
                'updated_at' => $parasite->updated_at ?? $now,
            ]);

            DB::table('parasite_photos')->insert([
                'parasites_id' => $parasite->id,
                'parasite_observations_id' => $observationId,
                'photo_path' => $parasite->photo_path,
                'created_at' => $parasite->created_at ?? $now,
                'updated_at' => $parasite->updated_at ?? $now,
            ]);
        }
    }

    public function down(): void
    {
        // Non-destructive data backfill; no rollback.
    }

    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
};
