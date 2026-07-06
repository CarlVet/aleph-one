<?php

namespace App\Support;

class NameFormatter
{
    /**
     * Normalize names with title casing while keeping common conjunctions and
     * prepositions lowercase, except when they are the first token.
     */
    public static function titleCaseWithMinorWords(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        if ($trimmed === '') {
            return '';
        }

        $minorWords = [
            'and',
            'or',
            'nor',
            'but',
            'yet',
            'so',
            'for',
            'of',
            'in',
            'on',
            'at',
            'by',
            'to',
            'from',
            'with',
            'without',
            'as',
            'per',
            'via',
            'a',
            'an',
            'the',
        ];
        $minorWordLookup = array_flip($minorWords);

        $words = preg_split('/\s+/', $trimmed) ?: [];

        $formatted = array_map(static function (string $word, int $index) use ($minorWordLookup): string {
            if ($word === '') {
                return $word;
            }

            if (self::isAcronymToken($word)) {
                return $word;
            }

            $lowerWord = mb_strtolower($word);

            if ($index > 0 && isset($minorWordLookup[$lowerWord])) {
                return $lowerWord;
            }

            return mb_strtoupper(mb_substr($lowerWord, 0, 1)).mb_substr($lowerWord, 1);
        }, $words, array_keys($words));

        return implode(' ', $formatted);
    }

    private static function isAcronymToken(string $word): bool
    {
        $trimmed = trim($word);
        if ($trimmed === '' || mb_strlen($trimmed) < 2) {
            return false;
        }

        if (preg_match('/\p{Ll}/u', $trimmed)) {
            return false;
        }

        return (bool) preg_match('/\p{Lu}/u', $trimmed);
    }
}
