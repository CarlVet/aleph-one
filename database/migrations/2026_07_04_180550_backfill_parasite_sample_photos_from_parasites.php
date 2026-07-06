<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->tableExists('parasite_samples')
            || ! $this->tableExists('parasite_sample_types')
            || ! $this->tableExists('parasite_sample_observations')
            || ! $this->tableExists('parasite_sample_photos')
            || ! $this->tableExists('parasites')
            || ! $this->tableExists('parasite_observations')
            || ! $this->tableExists('parasite_photos')) {
            return;
        }

        $wholeTypeId = DB::table('parasite_sample_types')
            ->where('name', 'Whole parasite')
            ->value('id');

        if (! $wholeTypeId) {
            return;
        }

        $now = now();

        $wholeSamples = DB::table('parasite_samples')
            ->select('id', 'parasites_id', 'people_id', 'photo_path', 'created_at', 'updated_at')
            ->where('parasite_sample_types_id', $wholeTypeId)
            ->orderBy('id')
            ->get();

        foreach ($wholeSamples as $sample) {
            $hasSamplePhotos = DB::table('parasite_sample_photos')
                ->where('parasite_samples_id', $sample->id)
                ->exists();

            if ($hasSamplePhotos) {
                continue;
            }

            $parasiteObservations = DB::table('parasite_observations')
                ->join('parasite_photos', 'parasite_photos.parasite_observations_id', '=', 'parasite_observations.id')
                ->where('parasite_observations.parasites_id', $sample->parasites_id)
                ->select(
                    'parasite_observations.observed_at',
                    'parasite_observations.notes',
                    'parasite_observations.people_id',
                    'parasite_observations.created_at',
                    'parasite_observations.updated_at',
                    'parasite_photos.photo_path',
                )
                ->orderBy('parasite_observations.id')
                ->get();

            if ($parasiteObservations->isNotEmpty()) {
                $coverPath = null;

                foreach ($parasiteObservations as $observation) {
                    $observationId = DB::table('parasite_sample_observations')->insertGetId([
                        'parasite_samples_id' => $sample->id,
                        'observed_at' => $observation->observed_at,
                        'notes' => $observation->notes,
                        'people_id' => $observation->people_id,
                        'created_at' => $observation->created_at ?? $now,
                        'updated_at' => $observation->updated_at ?? $now,
                    ]);

                    DB::table('parasite_sample_photos')->insert([
                        'parasite_samples_id' => $sample->id,
                        'parasite_sample_observations_id' => $observationId,
                        'photo_path' => $observation->photo_path,
                        'created_at' => $observation->created_at ?? $now,
                        'updated_at' => $observation->updated_at ?? $now,
                    ]);

                    $coverPath = $observation->photo_path;
                }

                if ($coverPath) {
                    DB::table('parasite_samples')
                        ->where('id', $sample->id)
                        ->update(['photo_path' => $coverPath]);
                }

                continue;
            }

            $parasitePhotoPath = DB::table('parasites')
                ->where('id', $sample->parasites_id)
                ->value('photo_path');

            $legacyPath = trim((string) ($sample->photo_path ?: $parasitePhotoPath ?: ''));
            if ($legacyPath === '') {
                continue;
            }

            $observationId = DB::table('parasite_sample_observations')->insertGetId([
                'parasite_samples_id' => $sample->id,
                'observed_at' => null,
                'notes' => null,
                'people_id' => $sample->people_id,
                'created_at' => $sample->created_at ?? $now,
                'updated_at' => $sample->updated_at ?? $now,
            ]);

            DB::table('parasite_sample_photos')->insert([
                'parasite_samples_id' => $sample->id,
                'parasite_sample_observations_id' => $observationId,
                'photo_path' => $legacyPath,
                'created_at' => $sample->created_at ?? $now,
                'updated_at' => $sample->updated_at ?? $now,
            ]);

            DB::table('parasite_samples')
                ->where('id', $sample->id)
                ->update(['photo_path' => $legacyPath]);
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
