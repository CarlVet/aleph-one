<?php

namespace App\Http\Controllers;

use App\Models\Humans;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class HumanSamplesCreateSelectionsController extends Controller
{
    public function humans(Request $request)
    {
        $projectId = session('selected_project_id');
        $perPage = (int) $request->integer('perPage', 50);
        if (! in_array($perPage, [10, 50, 100, 200], true)) {
            $perPage = 50;
        }

        $sortCol = (int) $request->integer('sort_col', 0);
        $sortDir = strtolower((string) $request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = Humans::query()
            ->where('projects_id', $projectId)
            ->orderByDesc('created_at')
            ->select([
                'id',
                'code',
                'first_name',
                'last_name',
                'sex',
                'date_of_birth',
                'ethnicity',
                'occupation',
                'city',
                'province',
                'phone',
                'email',
            ]);

        $this->applyHumansFilters($query, $request->input('filters', []));

        $sortMap = [
            1 => 'code',
            2 => 'first_name',
            3 => 'last_name',
            4 => 'sex',
            5 => 'date_of_birth',
            6 => 'ethnicity',
            7 => 'occupation',
            8 => 'city',
            9 => 'province',
            10 => 'phone',
            11 => 'email',
        ];

        if (isset($sortMap[$sortCol])) {
            $query->reorder()->orderBy($sortMap[$sortCol], $sortDir)->orderBy('id');
        }

        $humans = $query->paginate($perPage)->withQueryString();
        $humans->withPath(route('humans.create.humans'));

        return view('samples.humans.modals.humans_selection', [
            'humans' => $humans,
            'paginationPath' => route('humans.create.humans'),
        ]);
    }

    public function humansSearch(Request $request)
    {
        $projectId = session('selected_project_id');
        $q = trim((string) $request->input('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        $results = Humans::query()
            ->where('projects_id', $projectId)
            ->where(function (Builder $query) use ($q): void {
                $query
                    ->where('code', 'like', '%'.$q.'%')
                    ->orWhere('first_name', 'like', '%'.$q.'%')
                    ->orWhere('last_name', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%');
            })
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'code']);

        return response()->json(
            $results->map(fn ($h) => ['value' => $h->id, 'text' => $h->code])->values()
        );
    }

    private function applyHumansFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $colIndex => $value) {
            $v = trim((string) $value);
            if ($v === '') {
                continue;
            }

            match ((int) $colIndex) {
                1 => $query->where('code', 'like', '%'.$v.'%'),
                2 => $query->where('first_name', 'like', '%'.$v.'%'),
                3 => $query->where('last_name', 'like', '%'.$v.'%'),
                4 => $query->where('sex', 'like', '%'.$v.'%'),
                5 => $query->where('date_of_birth', 'like', '%'.$v.'%'),
                6 => $query->where('ethnicity', 'like', '%'.$v.'%'),
                7 => $query->where('occupation', 'like', '%'.$v.'%'),
                8 => $query->where('city', 'like', '%'.$v.'%'),
                9 => $query->where('province', 'like', '%'.$v.'%'),
                10 => $query->where('phone', 'like', '%'.$v.'%'),
                11 => $query->where('email', 'like', '%'.$v.'%'),
                default => null,
            };
        }
    }
}
