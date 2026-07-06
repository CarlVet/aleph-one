<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class MediaPreview
{
    /**
     * @return array<int, string>
     */
    public static function imageExtensions(): array
    {
        return ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    }

    public static function extension(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    public static function isImage(?string $path): bool
    {
        $extension = self::extension($path);

        return $extension !== null && in_array($extension, self::imageExtensions(), true);
    }

    public static function isPdf(?string $path): bool
    {
        return self::extension($path) === 'pdf';
    }

    public static function url(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        return Storage::url($path);
    }

    public static function exists(?string $path): bool
    {
        if ($path === null || trim($path) === '') {
            return false;
        }

        return Storage::disk('local')->exists($path);
    }
}
