<?php

namespace App\Http\Controllers;

use App\Models\Boxes;
use App\Models\Departments;
use App\Models\Humans;
use App\Models\Laboratories;
use App\Models\Locations;
use App\Models\Organizations;
use App\Models\Pathogens;
use App\Models\Protocols;
use App\Models\SamplingSites;
use App\Models\Studies;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NameValidationController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:laboratory,protocol,sampling_site,location,organization,department,patient,study,pathogen,box',
            'value' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
        ]);

        $type = (string) $validated['type'];

        if ($type === 'patient') {
            return $this->checkPatient($validated);
        }

        $value = $this->sanitize((string) ($validated['value'] ?? ''));
        $candidates = $this->candidatesForType($type);

        return $this->checkValueAgainstCandidates($value, $candidates);
    }

    public function checkOrganizationForRegistration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'nullable|string|max:255',
        ]);

        $value = $this->sanitize((string) ($validated['value'] ?? ''));
        $candidates = Organizations::query()->limit(600)->pluck('name')->filter()->values()->all();

        return $this->checkValueAgainstCandidates($value, $candidates);
    }

    /**
     * @param  list<string>  $candidates
     */
    private function checkValueAgainstCandidates(string $value, array $candidates): JsonResponse
    {
        if ($value === '') {
            return response()->json([
                'status' => 'empty',
                'message' => '',
                'suggestions' => [],
            ]);
        }

        $exact = $this->findExact($value, $candidates);
        if ($exact !== null) {
            return response()->json([
                'status' => 'exact',
                'message' => 'Name already exists. Go back and choose it from dropdown.',
                'match' => $exact,
                'suggestions' => [],
            ]);
        }

        $similar = $this->similarOptions($value, $candidates);
        if ($similar !== []) {
            return response()->json([
                'status' => 'similar',
                'message' => 'Input is similar to "'.$similar[0].'" option.',
                'match' => null,
                'suggestions' => $similar,
            ]);
        }

        return response()->json([
            'status' => 'new',
            'message' => 'Name is available.',
            'match' => null,
            'suggestions' => [],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function checkPatient(array $validated): JsonResponse
    {
        $firstName = $this->sanitize((string) ($validated['first_name'] ?? ''));
        $lastName = $this->sanitize((string) ($validated['last_name'] ?? ''));
        $value = trim($firstName.' '.$lastName);

        if ($value === '') {
            return response()->json([
                'status' => 'empty',
                'message' => '',
                'suggestions' => [],
            ]);
        }

        $query = Humans::query()->select(['first_name', 'last_name', 'projects_id']);
        $projectId = (int) session('selected_project_id', 0);
        if ($projectId > 0) {
            $query->where('projects_id', $projectId);
        }

        $candidates = $query
            ->limit(600)
            ->get()
            ->map(fn (Humans $human): string => trim((string) $human->first_name.' '.(string) $human->last_name))
            ->filter()
            ->values()
            ->all();

        $exact = $this->findExact($value, $candidates);
        if ($exact !== null) {
            return response()->json([
                'status' => 'exact',
                'message' => 'Name already exists. Go back and choose it from dropdown.',
                'match' => $exact,
                'suggestions' => [],
            ]);
        }

        $similar = $this->similarOptions($value, $candidates);
        if ($similar !== []) {
            return response()->json([
                'status' => 'similar',
                'message' => 'Input is similar to "'.$similar[0].'" option.',
                'match' => null,
                'suggestions' => $similar,
            ]);
        }

        return response()->json([
            'status' => 'new',
            'message' => 'Name is available.',
            'match' => null,
            'suggestions' => [],
        ]);
    }

    /**
     * @return list<string>
     */
    private function candidatesForType(string $type): array
    {
        return match ($type) {
            'laboratory' => Laboratories::query()->limit(600)->pluck('name')->filter()->values()->all(),
            'protocol' => Protocols::query()->limit(600)->pluck('name')->filter()->values()->all(),
            'sampling_site' => SamplingSites::query()->limit(600)->pluck('name')->filter()->values()->all(),
            'location' => Locations::query()->limit(600)->pluck('name')->filter()->values()->all(),
            'organization' => Organizations::query()->limit(600)->pluck('name')->filter()->values()->all(),
            'department' => Departments::query()->limit(600)->pluck('name')->filter()->values()->all(),
            'study' => Studies::query()->limit(600)->pluck('ref_key')->filter()->values()->all(),
            'pathogen' => Pathogens::query()->limit(600)->pluck('species')->filter()->values()->all(),
            'box' => $this->boxNameCandidates(),
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function boxNameCandidates(): array
    {
        $projectId = (int) session('selected_project_id', 0);

        $query = Boxes::query()
            ->whereNotNull('name')
            ->where('name', '!=', '');

        if ($projectId > 0) {
            $query->where('projects_id', $projectId);
        }

        return $query->limit(600)->pluck('name')->filter()->values()->all();
    }

    private function sanitize(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/u', '', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function canonical(string $value): string
    {
        $normalized = mb_strtolower($this->sanitize($value));
        $normalized = str_replace(['.', ',', ';', ':', '-', '_', '/', '\\', '(', ')'], ' ', $normalized);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        $tokens = preg_split('/\s+/', trim($normalized), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = array_values(array_unique($tokens));
        sort($tokens);

        return implode(' ', $tokens);
    }

    /**
     * @param  list<string>  $candidates
     */
    private function findExact(string $value, array $candidates): ?string
    {
        $target = mb_strtolower($this->sanitize($value));
        foreach ($candidates as $candidate) {
            if (mb_strtolower($this->sanitize((string) $candidate)) === $target) {
                return (string) $candidate;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $candidates
     * @return list<string>
     */
    private function similarOptions(string $value, array $candidates, int $threshold = 72, int $limit = 2): array
    {
        if (mb_strlen($this->sanitize($value)) < 3) {
            return [];
        }

        $valueCanonical = $this->canonical($value);
        $scored = [];

        foreach ($candidates as $candidate) {
            $candidateString = (string) $candidate;
            if ($this->findExact($value, [$candidateString]) !== null) {
                continue;
            }

            $candidateCanonical = $this->canonical($candidateString);
            similar_text($valueCanonical, $candidateCanonical, $pct);
            $tokenScore = $this->tokenOverlapScore($valueCanonical, $candidateCanonical);
            $prefixScore = $this->tokenPrefixScore($valueCanonical, $candidateCanonical);
            $score = max($pct, $tokenScore, $prefixScore);

            if ($score >= $threshold) {
                $scored[] = ['candidate' => $candidateString, 'score' => $score];
            }
        }

        usort($scored, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        $result = [];
        $seen = [];
        foreach ($scored as $entry) {
            $candidate = (string) $entry['candidate'];
            $key = mb_strtolower($this->sanitize($candidate));
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $candidate;
            }
            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    private function tokenOverlapScore(string $leftCanonical, string $rightCanonical): float
    {
        $left = array_values(array_filter(explode(' ', $leftCanonical)));
        $right = array_values(array_filter(explode(' ', $rightCanonical)));
        if ($left === [] || $right === []) {
            return 0.0;
        }

        $intersection = array_intersect($left, $right);
        $union = array_unique(array_merge($left, $right));
        if (count($union) === 0) {
            return 0.0;
        }

        return (count($intersection) / count($union)) * 100;
    }

    private function tokenPrefixScore(string $leftCanonical, string $rightCanonical): float
    {
        $left = array_values(array_filter(explode(' ', $leftCanonical)));
        $right = array_values(array_filter(explode(' ', $rightCanonical)));
        if ($left === [] || $right === []) {
            return 0.0;
        }

        $shorter = count($left) <= count($right) ? $left : $right;
        $longer = count($left) <= count($right) ? $right : $left;
        $matched = 0;

        foreach ($shorter as $shortToken) {
            foreach ($longer as $longToken) {
                if ($shortToken === $longToken || str_starts_with($longToken, $shortToken) || str_starts_with($shortToken, $longToken)) {
                    $matched++;
                    break;
                }
            }
        }

        return (count($shorter) > 0) ? (($matched / count($shorter)) * 100) : 0.0;
    }
}
