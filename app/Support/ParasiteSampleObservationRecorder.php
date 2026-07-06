<?php

namespace App\Support;

use App\Models\ParasiteSampleObservation;
use App\Models\ParasiteSamplePhoto;
use App\Models\ParasiteSamples;
use Illuminate\Support\Facades\Storage;

class ParasiteSampleObservationRecorder
{
    public static function createWithPhoto(
        ParasiteSamples $sample,
        string $photoPath,
        ?string $observedAt = null,
        ?string $notes = null,
        ?int $peopleId = null,
    ): ParasiteSampleObservation {
        $observation = ParasiteSampleObservation::query()->create([
            'parasite_samples_id' => $sample->id,
            'observed_at' => $observedAt ?: now()->toDateString(),
            'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : null,
            'people_id' => $peopleId,
        ]);

        ParasiteSamplePhoto::query()->create([
            'parasite_samples_id' => $sample->id,
            'parasite_sample_observations_id' => $observation->id,
            'photo_path' => $photoPath,
        ]);

        return $observation;
    }

    public static function ensureLegacyPhotoRecord(ParasiteSamples $sample): void
    {
        if (! $sample->photos()->exists()) {
            $path = trim((string) ($sample->photo_path ?? ''));
            if ($path !== '' && Storage::disk('local')->exists($path)) {
                self::createWithPhoto(
                    sample: $sample,
                    photoPath: $path,
                    observedAt: null,
                    notes: null,
                    peopleId: $sample->people_id,
                );
            }
        }

        $sample->loadMissing([
            'observations' => function ($query): void {
                $query->with([
                    'photo',
                    'people',
                    'comments.user.people',
                    'comments.replies.user.people',
                ]);
            },
        ]);
    }

    /**
     * @param  array<int, string>  $photoPaths
     */
    public static function createManyWithPhotos(
        ParasiteSamples $sample,
        array $photoPaths,
        ?string $observedAt = null,
        ?string $notes = null,
        ?int $peopleId = null,
    ): void {
        foreach ($photoPaths as $photoPath) {
            if (trim($photoPath) === '') {
                continue;
            }

            self::createWithPhoto(
                sample: $sample,
                photoPath: $photoPath,
                observedAt: $observedAt,
                notes: $notes,
                peopleId: $peopleId,
            );
        }

        $sample->syncCoverPhotoPath();
    }

    public static function deleteObservation(ParasiteSampleObservation $observation): void
    {
        $photoPath = trim((string) ($observation->photo?->photo_path ?? ''));
        $sample = $observation->parasiteSample;

        $observation->photo?->delete();
        $observation->delete();

        if ($sample) {
            $sample->syncCoverPhotoPath();
        }

        if ($photoPath !== '') {
            ParasitePhotoStorage::deleteFileIfUnreferenced($photoPath);
        }
    }
}
