<?php

namespace App\Support;

use App\Models\CultureObservation;
use App\Models\CulturePhoto;
use App\Models\Cultures;
use Illuminate\Support\Facades\Storage;

class CultureObservationRecorder
{
    public static function createWithPhoto(
        Cultures $culture,
        string $photoPath,
        ?string $observedAt = null,
        ?string $notes = null,
        ?int $peopleId = null,
    ): CultureObservation {
        $observation = CultureObservation::query()->create([
            'cultures_id' => $culture->id,
            'observed_at' => $observedAt ?: now()->toDateString(),
            'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : null,
            'people_id' => $peopleId,
        ]);

        CulturePhoto::query()->create([
            'cultures_id' => $culture->id,
            'culture_observations_id' => $observation->id,
            'photo_path' => $photoPath,
        ]);

        return $observation;
    }

    public static function ensureLegacyPhotoRecord(Cultures $culture): void
    {
        if ($culture->photos()->exists()) {
            return;
        }

        $path = trim((string) ($culture->photo_path ?? ''));
        if ($path === '' || ! Storage::disk('local')->exists($path)) {
            return;
        }

        self::createWithPhoto(
            culture: $culture,
            photoPath: $path,
            observedAt: null,
            notes: null,
            peopleId: $culture->people_id,
        );
    }
}
