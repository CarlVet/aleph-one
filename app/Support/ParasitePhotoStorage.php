<?php

namespace App\Support;

use App\Models\ParasitePhoto;
use App\Models\Parasites;
use App\Models\ParasiteSamplePhoto;
use App\Models\ParasiteSamples;
use Illuminate\Support\Facades\Storage;

class ParasitePhotoStorage
{
    public static function isPathReferenced(string $photoPath): bool
    {
        $photoPath = trim($photoPath);

        if ($photoPath === '') {
            return false;
        }

        return ParasitePhoto::query()->where('photo_path', $photoPath)->exists()
            || ParasiteSamplePhoto::query()->where('photo_path', $photoPath)->exists()
            || Parasites::query()->where('photo_path', $photoPath)->exists()
            || ParasiteSamples::query()->where('photo_path', $photoPath)->exists();
    }

    public static function deleteFileIfUnreferenced(string $photoPath): void
    {
        $photoPath = trim($photoPath);

        if ($photoPath === '' || self::isPathReferenced($photoPath)) {
            return;
        }

        if (Storage::disk('local')->exists($photoPath)) {
            Storage::disk('local')->delete($photoPath);
        }
    }
}
