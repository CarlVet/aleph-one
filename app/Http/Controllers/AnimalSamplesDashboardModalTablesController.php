<?php

namespace App\Http\Controllers;

use App\Models\AnimalSamples;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnimalSamplesDashboardModalTablesController extends Controller
{
    public function samples(Request $request)
    {
        $projectId = session('selected_project_id');
        $isGuestMode = $projectId === null;

        $query = $this->filteredAnimalSamplesQuery($request, $isGuestMode, $projectId)
            ->select([
                'animal_samples.id',
                'animal_samples.code',
                'animal_samples.date_collected',
                'animal_samples.processed',
                'animals.code as animal_code',
                'animal_species.name_common as species_name_common',
                'sample_types.name as sample_type',
            ])
            ->orderByDesc('animal_samples.created_at');

        $samples = $query->paginate(25)->withQueryString();
        $samples->withPath(route('animals.dashboard.modal.samples'));

        return view('samples.animals.modals.dashboard_samples_table', [
            'samples' => $samples,
            'paginationPath' => route('animals.dashboard.modal.samples'),
        ]);
    }

    public function animals(Request $request)
    {
        $projectId = session('selected_project_id');
        $isGuestMode = $projectId === null;

        $base = $this->filteredAnimalSamplesQuery($request, $isGuestMode, $projectId);

        $animals = $base
            ->select([
                'animals.id',
                'animals.code',
                'animal_species.name_common as species_name_common',
                'animals.sex',
                'animals.age',
                DB::raw('COUNT(DISTINCT animal_samples.id) as animal_samples_count'),
            ])
            ->groupBy('animals.id', 'animals.code', 'animal_species.name_common', 'animals.sex', 'animals.age')
            ->orderByDesc('animal_samples_count')
            ->paginate(25)
            ->withQueryString();

        $animals->withPath(route('animals.dashboard.modal.animals'));

        return view('samples.animals.modals.dashboard_animals_table', [
            'animals' => $animals,
            'paginationPath' => route('animals.dashboard.modal.animals'),
        ]);
    }

    public function species(Request $request)
    {
        $projectId = session('selected_project_id');
        $isGuestMode = $projectId === null;

        $base = $this->filteredAnimalSamplesQuery($request, $isGuestMode, $projectId);

        $species = $base
            ->select([
                'animal_species.id',
                'animal_species.name_common',
                'animal_species.name_scientific',
                'animal_species.family',
                DB::raw('COUNT(DISTINCT animal_samples.id) as animal_samples_count'),
                DB::raw('COUNT(DISTINCT animals.id) as animals_count'),
            ])
            ->groupBy('animal_species.id', 'animal_species.name_common', 'animal_species.name_scientific', 'animal_species.family')
            ->orderByDesc('animal_samples_count')
            ->paginate(25)
            ->withQueryString();

        $species->withPath(route('animals.dashboard.modal.species'));

        return view('samples.animals.modals.dashboard_species_table', [
            'species' => $species,
            'paginationPath' => route('animals.dashboard.modal.species'),
        ]);
    }

    public function sites(Request $request)
    {
        $projectId = session('selected_project_id');
        $isGuestMode = $projectId === null;

        $base = $this->filteredAnimalSamplesQuery($request, $isGuestMode, $projectId);

        $sites = $base
            ->select([
                'sampling_sites.id',
                'sampling_sites.name',
                'sampling_sites.site_type',
                'countries.name as country_name',
                'organizations.name as organization_name',
                DB::raw('COUNT(DISTINCT animal_samples.id) as animal_samples_count'),
                DB::raw('COUNT(DISTINCT animals.id) as animals_count'),
            ])
            ->leftJoin('countries', 'sampling_sites.countries_id', '=', 'countries.id')
            ->leftJoin('organizations', 'sampling_sites.organizations_id', '=', 'organizations.id')
            ->groupBy('sampling_sites.id', 'sampling_sites.name', 'sampling_sites.site_type', 'countries.name', 'organizations.name')
            ->orderByDesc('animal_samples_count')
            ->paginate(25)
            ->withQueryString();

        $sites->withPath(route('animals.dashboard.modal.sites'));

        return view('samples.animals.modals.dashboard_sites_table', [
            'sites' => $sites,
            'paginationPath' => route('animals.dashboard.modal.sites'),
        ]);
    }

    public function types(Request $request)
    {
        $projectId = session('selected_project_id');
        $isGuestMode = $projectId === null;

        $base = $this->filteredAnimalSamplesQuery($request, $isGuestMode, $projectId);

        // Avoid inflated animals_count due to join multiplicity.
        $base->select([
            'animal_samples.id as animal_sample_id',
            'animals.id as animal_id',
            'sample_types.id as sample_type_id',
            'sample_types.name as sample_type_name',
        ])
            ->distinct();

        $types = DB::query()
            ->fromSub($base, 'base')
            ->select([
                'sample_type_id as id',
                'sample_type_name as name',
                DB::raw('COUNT(DISTINCT animal_sample_id) as animal_samples_count'),
                DB::raw('COUNT(DISTINCT animal_id) as animals_count'),
            ])
            ->groupBy('sample_type_id', 'sample_type_name')
            ->orderByDesc('animal_samples_count')
            ->paginate(25)
            ->withQueryString();

        $types->withPath(route('animals.dashboard.modal.types'));

        return view('samples.animals.modals.dashboard_types_table', [
            'types' => $types,
            'paginationPath' => route('animals.dashboard.modal.types'),
        ]);
    }

    private function filteredAnimalSamplesQuery(Request $request, bool $isGuestMode, ?int $projectId)
    {
        $query = AnimalSamples::query()
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
            ->leftJoin('sample_types', 'animal_samples.sample_types_id', '=', 'sample_types.id')
            ->leftJoin('sampling_sites', 'animal_samples.sampling_sites_id', '=', 'sampling_sites.id');

        $sampleVisibility = (string) $request->query('sampleVisibility', 'all');

        if ($isGuestMode) {
            $query->where('animal_samples.processed', true);
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                    ->where('tubes.tubes_content_type', AnimalSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where(function ($q) use ($projectId) {
                $q->where('animal_samples.projects_id', $projectId)
                    ->orWhereExists(function ($sub) use ($projectId) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                            ->where('tubes.tubes_content_type', AnimalSamples::class)
                            ->where('tubes.projects_id', $projectId);
                    });
            });

            if ($sampleVisibility === 'processed_with_tubes') {
                $query->where('animal_samples.processed', true);
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                        ->where('tubes.tubes_content_type', AnimalSamples::class);
                });
            }
        }

        $animalSpeciesFilter = (string) $request->query('animal_species_filter', 'All');
        if ($animalSpeciesFilter !== '' && $animalSpeciesFilter !== 'All') {
            $query->where('animal_species.name_common', $animalSpeciesFilter);
        }

        $sampleTypeFilter = (string) $request->query('sample_type_filter', 'All');
        if ($sampleTypeFilter !== '' && $sampleTypeFilter !== 'All') {
            $query->where('sample_types.name', $sampleTypeFilter);
        }

        $samplingSiteFilter = (string) $request->query('sampling_site_filter', 'All');
        if ($samplingSiteFilter !== '' && $samplingSiteFilter !== 'All') {
            $query->where('sampling_sites.name', $samplingSiteFilter);
        }

        $subProjectFilter = (string) $request->query('subProjectFilter', '');
        if ($subProjectFilter !== '' && $subProjectFilter !== 'All') {
            $query->whereExists(function ($sub) use ($subProjectFilter) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'animal_samples.id')
                    ->where('sub_project_assignments.assignable_type', AnimalSamples::class)
                    ->where('sub_projects.code', $subProjectFilter);
            });
        }

        $startDate = (string) $request->query('startDate', '');
        $endDate = (string) $request->query('endDate', '');
        if ($startDate !== '' && $endDate !== '') {
            $query->whereBetween('animal_samples.date_collected', [$startDate, $endDate]);
        } elseif ($startDate !== '') {
            $query->where('animal_samples.date_collected', '>=', $startDate);
        } elseif ($endDate !== '') {
            $query->where('animal_samples.date_collected', '<=', $endDate);
        }

        return $query;
    }
}
