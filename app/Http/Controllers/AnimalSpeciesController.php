<?php

namespace App\Http\Controllers;

use App\Models\AnimalSpecies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnimalSpeciesController extends Controller
{
    public function create()
    {
        return view('animals.species.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_common' => 'required|string|max:255|unique:animal_species',
            'name_scientific' => 'required|string|max:255|unique:animal_species',
            'genus' => 'nullable|string|max:255',
            'family' => 'nullable|string|max:255',
            'order' => 'nullable|string|max:255',
            'class' => 'nullable|string|max:255',
            'phylum' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        AnimalSpecies::create($request->all());

        // Check if this is a modal submission
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Animal species created successfully!',
            ]);
        }

        return redirect()->back()
            ->with('success', 'Animal species created successfully!');
    }

    public function checkDuplicate(Request $request)
    {
        $field = $request->input('field');
        $value = $this->sanitize((string) $request->input('value', ''));

        if (! in_array($field, ['name_common', 'name_scientific'], true)) {
            return response()->json(['error' => 'Invalid field'], 400);
        }

        if ($value === '') {
            return response()->json([
                'status' => 'empty',
                'exists' => false,
                'suggestions' => [],
                'message' => null,
            ]);
        }

        $candidates = AnimalSpecies::query()
            ->pluck($field)
            ->filter()
            ->map(fn ($candidate): string => (string) $candidate)
            ->values()
            ->all();

        $exactMatch = $this->findExact($value, $candidates);
        if ($exactMatch !== null) {
            return response()->json([
                'status' => 'exact',
                'exists' => true,
                'match' => $exactMatch,
                'suggestions' => [],
                'message' => 'Name already exists. Go back and choose it from dropdown.',
            ]);
        }

        $similar = $this->similarOptions($value, $candidates);
        if ($similar !== []) {
            return response()->json([
                'status' => 'similar',
                'exists' => false,
                'match' => null,
                'suggestions' => $similar,
                'message' => 'Input is similar to "'.$similar[0].'" option.',
            ]);
        }

        return response()->json([
            'status' => 'new',
            'exists' => false,
            'match' => null,
            'suggestions' => [],
            'message' => 'Name is available.',
        ]);
    }

    private function sanitize(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/u', '', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * @param  list<string>  $candidates
     */
    private function findExact(string $value, array $candidates): ?string
    {
        $normalizedValue = mb_strtolower($this->sanitize($value));
        foreach ($candidates as $candidate) {
            if (mb_strtolower($this->sanitize((string) $candidate)) === $normalizedValue) {
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
        if (mb_strlen($value) < 3) {
            return [];
        }

        $scored = [];
        foreach ($candidates as $candidate) {
            $candidateString = (string) $candidate;
            if ($this->findExact($value, [$candidateString]) !== null) {
                continue;
            }

            similar_text(
                mb_strtolower($this->sanitize($value)),
                mb_strtolower($this->sanitize($candidateString)),
                $pct
            );

            if ($pct >= $threshold) {
                $scored[] = ['candidate' => $candidateString, 'score' => $pct];
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
}
