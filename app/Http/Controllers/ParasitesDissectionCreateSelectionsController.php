<?php

namespace App\Http\Controllers;

use App\Enums\ParasiteStatus;
use App\Http\Controllers\Concerns\FiltersContentDetails;
use App\Models\AnimalSamples;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\Parasites;
use App\Support\ParasiteOriginDetailsPresenter;
use App\Support\ProjectPermission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ParasitesDissectionCreateSelectionsController extends Controller
{
    use FiltersContentDetails;

    public function parasites(Request $request): View
    {
        $projectId = (int) session('selected_project_id');

        $filters = (array) $request->input('filters', []);
        $perPage = $this->resolvePerPage($request);
        $sortCol = $request->input('sort_col');
        $sortDir = $this->resolveSortDir($request);

        $aliasSubquery = Parasites::storageTubeAliasSubquery();

        $query = Parasites::query()
            ->where('parasites.projects_id', $projectId)
            ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
            ->leftJoin('people', 'parasites.people_id', '=', 'people.id')
            ->leftJoin('laboratories', 'parasites.laboratories_id', '=', 'laboratories.id')
            ->leftJoin('human_samples as hs', function ($join): void {
                $join->on('parasites.parasites_origin_id', '=', 'hs.id')
                    ->where('parasites.parasites_origin_type', '=', HumanSamples::class);
            })
            ->leftJoin('animal_samples as ans', function ($join): void {
                $join->on('parasites.parasites_origin_id', '=', 'ans.id')
                    ->where('parasites.parasites_origin_type', '=', AnimalSamples::class);
            })
            ->leftJoin('environment_samples as es', function ($join): void {
                $join->on('parasites.parasites_origin_id', '=', 'es.id')
                    ->where('parasites.parasites_origin_type', '=', EnvironmentSamples::class);
            })
            ->select([
                'parasites.*',
                'parasite_species.name_scientific as species_name',
                'laboratories.name as lab_name',
                DB::raw('COALESCE(hs.code, ans.code, es.code) as origin_code_sort'),
                DB::raw($aliasSubquery.' as parasite_alias_code'),
                DB::raw("TRIM(CONCAT(COALESCE(people.title,''),' ',COALESCE(people.first_name,''),' ',COALESCE(people.last_name,''))) as identified_by"),
            ]);

        $this->applyFilters($query, $filters, $aliasSubquery);
        $this->applySorting($query, $sortCol, $sortDir, $aliasSubquery);

        /** @var LengthAwarePaginator $parasites */
        $parasites = $query->paginate($perPage)->withQueryString();
        ParasiteOriginDetailsPresenter::hydrate($parasites->getCollection());

        $user = Auth::user();
        $editableParasiteIds = $parasites->getCollection()
            ->filter(function ($parasite) use ($user, $projectId): bool {
                return $user && ProjectPermission::canEditOrDelete(
                    $user,
                    $projectId,
                    (int) $parasite->people_id,
                    'parasite_samples'
                );
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return view('samples.parasites.modals.parasites_selection', [
            'parasites' => $parasites,
            'status_options' => ParasiteStatus::options(),
            'editable_parasite_ids' => $editableParasiteIds,
        ]);
    }

    public function parasitesSearch(Request $request)
    {
        $projectId = (int) session('selected_project_id');
        $q = trim((string) $request->input('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        $aliasSubquery = Parasites::storageTubeAliasSubquery();

        $results = Parasites::query()
            ->where('parasites.projects_id', $projectId)
            ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
            ->select([
                'parasites.id',
                'parasites.code',
                'parasite_species.name_scientific as species_name',
                DB::raw($aliasSubquery.' as parasite_alias_code'),
            ])
            ->where(function ($query) use ($q, $aliasSubquery): void {
                $query->where('parasites.code', 'like', '%'.$q.'%')
                    ->orWhereRaw($aliasSubquery.' like ?', ['%'.$q.'%'])
                    ->orWhere('parasite_species.name_scientific', 'like', '%'.$q.'%');
            })
            ->orderBy('parasites.code')
            ->limit(50)
            ->get()
            ->map(fn ($row) => [
                'value' => $row->id,
                'text' => $row->code,
                'code' => $row->code,
                'alias_code' => $row->parasite_alias_code,
                'species_name' => $row->species_name,
            ])
            ->values();

        return response()->json($results);
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->input('perPage', 50);
        $allowed = [10, 50, 100, 200];

        if (! in_array($perPage, $allowed, true)) {
            return 50;
        }

        return $perPage;
    }

    private function resolveSortDir(Request $request): string
    {
        return $request->input('sort_dir') === 'desc' ? 'desc' : 'asc';
    }

    private function applySorting($query, $sortCol, string $sortDir, string $aliasSubquery): void
    {
        $map = [
            1 => 'parasites.code',
            3 => 'parasites.status',
            4 => 'parasite_species.name_scientific',
            5 => 'parasites.parasites_origin_type',
            8 => 'parasites.date_identified',
            9 => 'people.last_name',
            10 => 'laboratories.name',
        ];

        if ($sortCol === null || $sortCol === '') {
            $query->orderBy('parasites.code');

            return;
        }

        $idx = (int) $sortCol;

        if ($idx === 2) {
            $query->orderByRaw($aliasSubquery.' '.$sortDir);
            $query->orderBy('parasites.code');

            return;
        }

        if ($idx === 7) {
            $query->orderByRaw('origin_code_sort '.$sortDir);
            $query->orderBy('parasites.code');

            return;
        }

        $col = $map[$idx] ?? null;
        if (! $col) {
            $query->orderBy('parasites.code');

            return;
        }

        $query->orderBy($col, $sortDir)->orderBy('parasites.code');
    }

    private function applyFilters($query, array $filters, string $aliasSubquery): void
    {
        $code = trim((string) ($filters[1] ?? ''));
        if ($code !== '') {
            $query->where('parasites.code', 'like', '%'.$code.'%');
        }

        $alias = trim((string) ($filters[2] ?? ''));
        if ($alias !== '') {
            $query->whereRaw($aliasSubquery.' like ?', ['%'.$alias.'%']);
        }

        $status = trim((string) ($filters[3] ?? ''));
        if ($status !== '') {
            $matchedStatus = ParasiteStatus::tryFromLabel($status);
            if ($matchedStatus) {
                $query->where('parasites.status', $matchedStatus->value);
            } else {
                $query->where('parasites.status', 'like', '%'.$status.'%');
            }
        }

        $species = trim((string) ($filters[4] ?? ''));
        if ($species !== '') {
            $query->where('parasite_species.name_scientific', 'like', '%'.$species.'%');
        }

        $originType = trim((string) ($filters[5] ?? ''));
        if ($originType !== '') {
            $this->applyOriginTypeFilter($query, $originType);
        }

        $contentDetails = trim((string) ($filters[6] ?? ''));
        if ($contentDetails !== '') {
            $this->applyMultiWordFilter($query, $contentDetails, function (Builder $detailsQuery, string $value): void {
                $detailsQuery
                    ->whereHasMorph('parasites_origin', [HumanSamples::class], function (Builder $sampleQuery) use ($value): void {
                        $sampleQuery->where('code', 'like', '%'.$value.'%')
                            ->orWhere('date_collected', 'like', '%'.$value.'%')
                            ->orWhereHas('sample_types', fn (Builder $typeQuery) => $typeQuery->where('name', 'like', '%'.$value.'%'))
                            ->orWhereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$value.'%'))
                            ->orWhereHas('tubes', fn (Builder $tubeQuery) => $tubeQuery->where('alias_code', 'like', '%'.$value.'%'));
                    })
                    ->orWhereHasMorph('parasites_origin', [AnimalSamples::class], function (Builder $sampleQuery) use ($value): void {
                        $sampleQuery->where('code', 'like', '%'.$value.'%')
                            ->orWhere('date_collected', 'like', '%'.$value.'%')
                            ->orWhereHas('animals', function (Builder $animalQuery) use ($value): void {
                                $animalQuery->where('field_label', 'like', '%'.$value.'%')
                                    ->orWhereHas('animal_species', fn (Builder $speciesQuery) => $speciesQuery
                                        ->where('name_common', 'like', '%'.$value.'%')
                                        ->orWhere('name_scientific', 'like', '%'.$value.'%'));
                            })
                            ->orWhereHas('sample_types', fn (Builder $typeQuery) => $typeQuery->where('name', 'like', '%'.$value.'%'))
                            ->orWhereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$value.'%'))
                            ->orWhereHas('tubes', fn (Builder $tubeQuery) => $tubeQuery->where('alias_code', 'like', '%'.$value.'%'));
                    })
                    ->orWhereHasMorph('parasites_origin', [EnvironmentSamples::class], function (Builder $sampleQuery) use ($value): void {
                        $sampleQuery->where('code', 'like', '%'.$value.'%')
                            ->orWhere('area', 'like', '%'.$value.'%')
                            ->orWhere('date_collected', 'like', '%'.$value.'%')
                            ->orWhereHas('environment_sample_types', fn (Builder $typeQuery) => $typeQuery->where('name', 'like', '%'.$value.'%'))
                            ->orWhereHas('sampling_sites', fn (Builder $siteQuery) => $siteQuery->where('name', 'like', '%'.$value.'%'))
                            ->orWhereHas('tubes', fn (Builder $tubeQuery) => $tubeQuery->where('alias_code', 'like', '%'.$value.'%'));
                    });
            });
        }

        $originCode = trim((string) ($filters[7] ?? ''));
        if ($originCode !== '') {
            $query->whereRaw('origin_code_sort like ?', ['%'.$originCode.'%']);
        }

        $dateIdentified = trim((string) ($filters[8] ?? ''));
        if ($dateIdentified !== '') {
            $query->where('parasites.date_identified', 'like', '%'.$dateIdentified.'%');
        }

        $identifiedBy = trim((string) ($filters[9] ?? ''));
        if ($identifiedBy !== '') {
            $query->where(function ($q) use ($identifiedBy): void {
                $q->where('people.first_name', 'like', '%'.$identifiedBy.'%')
                    ->orWhere('people.last_name', 'like', '%'.$identifiedBy.'%')
                    ->orWhere('people.title', 'like', '%'.$identifiedBy.'%');
            });
        }

        $lab = trim((string) ($filters[10] ?? ''));
        if ($lab !== '') {
            $query->where('laboratories.name', 'like', '%'.$lab.'%');
        }
    }

    private function applyOriginTypeFilter(Builder $query, string $filter): void
    {
        $value = strtolower(trim($filter));

        $matchedClass = match (true) {
            str_contains($value, 'human') => HumanSamples::class,
            str_contains($value, 'animal') => AnimalSamples::class,
            str_contains($value, 'environment') || str_contains($value, 'env') => EnvironmentSamples::class,
            default => null,
        };

        if ($matchedClass) {
            $query->where('parasites.parasites_origin_type', $matchedClass);

            return;
        }

        $query->where('parasites.parasites_origin_type', 'like', '%'.$filter.'%');
    }
}
