<?php

namespace App\Services\Imports;

class HeaderNormalizer
{
    public static function normalize(string $header): string
    {
        $h = $header;

        if (str_starts_with($h, "\xEF\xBB\xBF")) {
            $h = substr($h, 3);
        }

        $iconv = @iconv('UTF-8', 'UTF-8//IGNORE', $h);
        if (is_string($iconv)) {
            $h = $iconv;
        }

        $h = str_replace("\u{00A0}", ' ', $h);
        $h = strtolower(trim($h));

        $replaced = preg_replace('/[^\p{L}\p{N}]+/u', '_', $h);
        if (! is_string($replaced)) {
            $replaced = preg_replace('/[^a-z0-9]+/i', '_', $h) ?: $h;
        }
        $h = trim($replaced, '_');

        return $h;
    }
}
