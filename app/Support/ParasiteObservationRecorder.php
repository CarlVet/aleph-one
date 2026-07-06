<?php

namespace App\Support;

use App\Models\ParasiteObservation;
use App\Models\ParasitePhoto;
use App\Models\Parasites;
use Illuminate\Support\Facades\Storage;

class ParasiteObservationRecorder
{
    public static function createWithPhoto(
        Parasites $parasite,
        string $photoPath,
        ?string $observedAt = null,
        ?string $notes = null,
        ?int $peopleId = null,
    ): ParasiteObservation {
        $observation = ParasiteObservation::query()->create([
            'parasites_id' => $parasite->id,
            'observed_at' => $observedAt ?: now()->toDateString(),
            'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : null,
            'people_id' => $peopleId,
        ]);

        ParasitePhoto::query()->create([
            'parasites_id' => $parasite->id,
            'parasite_observations_id' => $observation->id,
            'photo_path' => $photoPath,
        ]);

        return $observation;
    }

    public static function ensureLegacyPhotoRecord(Parasites $parasite): void
    {
        if ($parasite->photos()->exists()) {
            return;
        }

        $path = trim((string) ($parasite->photo_path ?? ''));
        if ($path === '' || ! Storage::disk('local')->exists($path)) {
            return;
        }

        self::createWithPhoto(
            parasite: $parasite,
            photoPath: $path,
            observedAt: null,
            notes: null,
            peopleId: $parasite->people_id,
        );
    }

    /**
     * @param  array<int, string>  $photoPaths
     */
    public static function createManyWithPhotos(
        Parasites $parasite,
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
                parasite: $parasite,
                photoPath: $photoPath,
                observedAt: $observedAt,
                notes: $notes,
                peopleId: $peopleId,
            );
        }

        $parasite->syncCoverPhotoPath();
    }

    public static function deleteObservation(ParasiteObservation $observation): void
    {
        $photoPath = trim((string) ($observation->photo?->photo_path ?? ''));
        $parasite = $observation->parasite;

        $observation->photo?->delete();
        $observation->delete();

        if ($parasite) {
            $parasite->syncCoverPhotoPath();
        }

        if ($photoPath !== '') {
            ParasitePhotoStorage::deleteFileIfUnreferenced($photoPath);
        }
    }
}
