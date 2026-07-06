<?php

namespace App\Services\Imports;

class ColumnMatcher
{
    /**
     * @param  list<string>  $headers
     * @param  array<string, list<string>>  $synonymsByField
     * @return array{fieldToIndex: array<string,int>, issues: list<string>, normalizedHeaders: list<string>}
     */
    public function match(array $headers, array $synonymsByField): array
    {
        $normalized = array_values(array_map(fn ($h) => HeaderNormalizer::normalize((string) $h), $headers));

        $fieldToIndex = [];
        $issues = [];

        foreach ($synonymsByField as $field => $synonyms) {
            $synonymsNorm = array_values(array_unique(array_map(fn ($s) => HeaderNormalizer::normalize((string) $s), $synonyms)));

            $bestIdx = null;
            $bestScore = -1;

            foreach ($normalized as $idx => $h) {
                foreach ($synonymsNorm as $s) {
                    if ($h === $s) {
                        $bestIdx = $idx;
                        $bestScore = 1000;
                        break 2;
                    }

                    $score = $this->similarityScore($h, $s);
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestIdx = $idx;
                    }
                }
            }

            if ($bestIdx !== null && $bestScore >= 70) {
                $fieldToIndex[$field] = $bestIdx;
            }
        }

        return [
            'fieldToIndex' => $fieldToIndex,
            'issues' => $issues,
            'normalizedHeaders' => $normalized,
        ];
    }

    private function similarityScore(string $a, string $b): int
    {
        $a = (string) $a;
        $b = (string) $b;
        if ($a === '' || $b === '') {
            return 0;
        }

        similar_text($a, $b, $pct);

        return (int) round($pct);
    }
}
