<?php

namespace App\Http\Controllers;

use App\Models\Animals;
use App\Models\Humans;
use App\Models\Organizations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AnimalSamplesCreateSelectionsController extends Controller
{
    public function animals(Request $request)
    {
        $projectId = session('selected_project_id');
        $perPage = (int) $request->integer('perPage', 50);
        if (! in_array($perPage, [10, 50, 100, 200], true)) {
            $perPage = 50;
        }

        $sortCol = (int) $request->integer('sort_col', 0);
        $sortDir = strtolower((string) $request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = Animals::query()
            ->where('animals.projects_id', $projectId)
            ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
            ->leftJoin('humans', function ($join): void {
                $join->on('animals.owner_id', '=', 'humans.id')
                    ->where('animals.owner_type', '=', Humans::class);
            })
            ->leftJoin('organizations', function ($join): void {
                $join->on('animals.owner_id', '=', 'organizations.id')
                    ->where('animals.owner_type', '=', Organizations::class);
            })
            ->select([
                'animals.id',
                'animals.code',
                'animals.field_label',
                'animals.sex',
                'animals.age',
                'animals.owner_type',
                'animals.owner_id',
                'animal_species.name_common as species_name_common',
                'humans.code as owner_human_code',
                'humans.first_name as owner_first_name',
                'humans.last_name as owner_last_name',
                'organizations.name as owner_organization_name',
            ])
            ->orderByDesc('animals.created_at');

        $this->applyAnimalsFilters($query, $request->input('filters', []));

        $sortMap = [
            1 => 'animals.code',
            2 => 'animals.field_label',
            3 => 'animal_species.name_common',
            4 => 'animals.sex',
            5 => 'animals.age',
            6 => 'humans.last_name',
        ];

        if (isset($sortMap[$sortCol])) {
            $query->reorder()->orderBy($sortMap[$sortCol], $sortDir)->orderBy('animals.id');
        }

        $animals = $query->paginate($perPage)->withQueryString();
        $animals->withPath(route('animals.create.animals'));

        return view('samples.animals.modals.animals_selection', [
            'animals' => $animals,
            'paginationPath' => route('animals.create.animals'),
        ]);
    }

    public function animalsSearch(Request $request)
    {
        $projectId = session('selected_project_id');
        $q = trim((string) $request->input('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        $results = Animals::query()
            ->where('projects_id', $projectId)
            ->where(function (Builder $query) use ($q): void {
                $query
                    ->where('code', 'like', '%'.$q.'%')
                    ->orWhere('field_label', 'like', '%'.$q.'%');
            })
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'code']);

        return response()->json(
            $results->map(fn ($a) => ['value' => $a->id, 'text' => $a->code])->values()
        );
    }

    private function applyAnimalsFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $colIndex => $value) {
            $v = trim((string) $value);
            if ($v === '') {
                continue;
            }

            match ((int) $colIndex) {
                1 => $query->where('animals.code', 'like', '%'.$v.'%'),
                2 => $query->where('animals.field_label', 'like', '%'.$v.'%'),
                3 => $query->where('animal_species.name_common', 'like', '%'.$v.'%'),
                4 => $query->where('animals.sex', 'like', '%'.$v.'%'),
                5 => $query->where('animals.age', 'like', '%'.$v.'%'),
                6 => $query->where(function (Builder $q) use ($v): void {
                    $q
                        ->where('humans.first_name', 'like', '%'.$v.'%')
                        ->orWhere('humans.last_name', 'like', '%'.$v.'%')
                        ->orWhere('organizations.name', 'like', '%'.$v.'%');
                }),
                default => null,
            };
        }
    }
}
