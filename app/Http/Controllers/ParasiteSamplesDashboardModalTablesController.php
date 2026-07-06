<?php

namespace App\Http\Controllers;

use App\Models\AnimalSamples;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\ParasiteSamples;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ParasiteSamplesDashboardModalTablesController extends Controller
{
    public function all(Request $request): View
    {
        return $this->table($request, null, route('parasites.dashboard.modal.samples'));
    }

    public function human(Request $request): View
    {
        return $this->table($request, HumanSamples::class, route('parasites.dashboard.modal.samples.human'));
    }

    public function animal(Request $request): View
    {
        return $this->table($request, AnimalSamples::class, route('parasites.dashboard.modal.samples.animal'));
    }

    public function environment(Request $request): View
    {
        return $this->table($request, EnvironmentSamples::class, route('parasites.dashboard.modal.samples.environment'));
    }

    private function table(Request $request, ?string $originType, string $paginationPath): View
    {
        $projectId = session('selected_project_id');
        $isGuestMode = $projectId === null;
        $sampleVisibility = (string) $request->query('sampleVisibility', 'all');

        $query = ParasiteSamples::query()
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
            ->leftJoin('parasite_sample_types', 'parasite_samples.parasite_sample_types_id', '=', 'parasite_sample_types.id')
            ->leftJoin('people', 'parasites.people_id', '=', 'people.id')
            ->leftJoin('human_samples', function ($join) {
                $join->on('parasites.parasites_origin_id', '=', 'human_samples.id')
                    ->where('parasites.parasites_origin_type', HumanSamples::class);
            })
            ->leftJoin('animal_samples', function ($join) {
                $join->on('parasites.parasites_origin_id', '=', 'animal_samples.id')
                    ->where('parasites.parasites_origin_type', AnimalSamples::class);
            })
            ->leftJoin('environment_samples', function ($join) {
                $join->on('parasites.parasites_origin_id', '=', 'environment_samples.id')
                    ->where('parasites.parasites_origin_type', EnvironmentSamples::class);
            })
            ->leftJoin('sampling_sites as ss_human', 'human_samples.sampling_sites_id', '=', 'ss_human.id')
            ->leftJoin('countries as human_countries', 'human_countries.id', '=', 'ss_human.countries_id')
            ->leftJoin('humans', 'humans.id', '=', 'human_samples.humans_id')
            ->leftJoin('sampling_sites as ss_animal', 'animal_samples.sampling_sites_id', '=', 'ss_animal.id')
            ->leftJoin('countries as animal_countries', 'animal_countries.id', '=', 'ss_animal.countries_id')
            ->leftJoin('animals', 'animals.id', '=', 'animal_samples.animals_id')
            ->leftJoin('animal_species', 'animal_species.id', '=', 'animals.animal_species_id')
            ->leftJoin('sampling_sites as ss_env', 'environment_samples.sampling_sites_id', '=', 'ss_env.id')
            ->leftJoin('countries as environment_countries', 'environment_countries.id', '=', 'ss_env.countries_id')
            ->select([
                'parasite_samples.id',
                'parasite_samples.code',
                'parasites.parasites_origin_type',
                'parasite_species.name_scientific as parasite_species',
                'parasites.stage',
                'parasites.sex',
                'parasites.state',
                DB::raw('COALESCE(ss_human.name, ss_animal.name, ss_env.name) as sampling_site_name'),
                DB::raw("TRIM(COALESCE(people.title,'') || ' ' || COALESCE(people.first_name,'') || ' ' || COALESCE(people.last_name,'')) as identified_by"),
                'parasites.date_identified',
                'humans.sex as human_sex',
                'humans.ethnicity as human_ethnicity',
                'humans.occupation as human_occupation',
                'human_countries.name as human_country',
                'animal_species.name_common as animal_species',
                'animals.sex as animal_sex',
                'animals.age as animal_age',
                'animal_countries.name as animal_country',
                'environment_countries.name as environment_country',
            ])
            ->orderByDesc('parasite_samples.id');

        if ($isGuestMode) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'parasite_samples.id')
                    ->where('tubes.tubes_content_type', ParasiteSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where('parasite_samples.projects_id', $projectId);

            if ($sampleVisibility === 'processed_with_tubes') {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'parasite_samples.id')
                        ->where('tubes.tubes_content_type', ParasiteSamples::class);
                });
            }
        }

        if ($originType) {
            $query->where('parasites.parasites_origin_type', $originType);
        }

        $this->applyFiltersFromRequest($query, $request);

        $samples = $query->paginate(25, pageName: 'dashboard_samples_page');

        return view('samples.parasites.modals.dashboard_samples_table', compact('samples', 'paginationPath'));
    }

    private function applyFiltersFromRequest(Builder $query, Request $request): void
    {
        $parasiteSpeciesFilter = (string) $request->query('parasiteSpeciesFilter', '');
        if ($parasiteSpeciesFilter !== '') {
            $query->where('parasite_species.name_scientific', $parasiteSpeciesFilter);
        }

        $parasiteGenusFilter = (string) $request->query('parasiteGenusFilter', '');
        if ($parasiteGenusFilter !== '') {
            $query->where('parasite_species.genus', $parasiteGenusFilter);
        }

        $parasiteFamilyFilter = (string) $request->query('parasiteFamilyFilter', '');
        if ($parasiteFamilyFilter !== '') {
            $query->where('parasite_species.family', $parasiteFamilyFilter);
        }

        $sampleTypeFilter = (string) $request->query('sampleTypeFilter', '');
        if ($sampleTypeFilter !== '') {
            $query->where('parasite_sample_types.name', $sampleTypeFilter);
        }

        $stageFilter = (string) $request->query('stageFilter', '');
        if ($stageFilter !== '') {
            $query->where('parasites.stage', $stageFilter);
        }

        $sexFilter = (string) $request->query('sexFilter', '');
        if ($sexFilter !== '') {
            $query->where('parasites.sex', $sexFilter);
        }

        $stateFilter = (string) $request->query('stateFilter', '');
        if ($stateFilter !== '') {
            $query->where('parasites.state', $stateFilter);
        }

        $originTypeFilter = (string) $request->query('originTypeFilter', 'all');
        if ($originTypeFilter !== '' && $originTypeFilter !== 'all') {
            $originType = match ($originTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                default => null,
            };

            if ($originType) {
                $query->where('parasites.parasites_origin_type', $originType);
            }
        }

        $startDate = (string) $request->query('startDate', '');
        $endDate = (string) $request->query('endDate', '');
        $originDateExpr = DB::raw('COALESCE(human_samples.date_collected, animal_samples.date_collected, environment_samples.date_collected)');

        if ($startDate !== '' && $endDate !== '') {
            $query->whereBetween($originDateExpr, [$startDate, $endDate]);
        } elseif ($startDate !== '') {
            $query->where($originDateExpr, '>=', $startDate);
        } elseif ($endDate !== '') {
            $query->where($originDateExpr, '<=', $endDate);
        }
    }
}
