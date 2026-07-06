<?php

namespace App\Services;

use App\Models\Countries;
use App\Models\Experiments;
use App\Models\Laboratories;
use App\Models\NucleicAcids;
use App\Models\Organizations;
use App\Models\People;
use App\Models\Projects;
use App\Models\Protocols;
use App\Models\Techniques;
use App\Models\Tubes;
use App\Support\LookupTableData;

class NucleicAcidsService
{
    private const DEFAULT_NUCLEIC_TYPES = [
        'genomic DNA',
        'complementary DNA',
        'plasmid DNA',
        'mitochondrial DNA',
        'RNA',
        'Purified PCR product',
    ];

    protected $projectId;

    public function __construct()
    {
        $this->projectId = session('selected_project_id');
    }

    public function getProjectId()
    {
        return $this->projectId;
    }

    public function isGuestMode()
    {
        return $this->projectId === null;
    }

    public function check_or_create($model, $conditions, $attributes = [])
    {
        $existing_value = $model::where($conditions)->first();

        if (! $existing_value) {
            $model::create(array_merge($conditions, $attributes));

            return $model::where($conditions)->first()->id;
        } else {
            return $existing_value->id;
        }
    }

    public function laboratories_by_country()
    {
        // Retrieve laboratories
        $laboratories = Laboratories::with('countries', 'organization')->get();

        // Initialize an array to organize labs by country
        $labs_by_country = [];

        // Group laboratories by country
        foreach ($laboratories as $lab) {
            $country = $lab->countries->name ?? 'Unknown country';
            $name = $lab['name'] ?? '';

            if ($name) {
                $labs_by_country[$country][] = [
                    'name' => $name,
                ];
            }
        }

        // Sort countries alphabetically
        ksort($labs_by_country);

        // Sort labs alphabetically within each country
        foreach ($labs_by_country as $country => $labs_list) {
            usort($labs_by_country[$country], function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
        }

        return $labs_by_country;
    }

    public function assign()
    {
        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            // In guest mode, show only public nucleic acids
            $nucleic_acids = NucleicAcids::with(
                'protocols',
                'nucleic_content',
                'laboratories',
                'people',
                'projects',
            )->whereHas('tubes', function ($query) {
                $query->where('is_private', false);
            })->get();

            // For guest mode, return empty collections for people since we don't have a specific project
            $people = People::all();
        } else {
            // In project mode, show nucleic acids from the selected project
            $nucleic_acids = NucleicAcids::with(
                'protocols',
                'nucleic_content',
                'laboratories',
                'people',
                'projects',
            )->where('projects_id', $this->projectId)
                ->get();

            $people = Projects::find($this->projectId)->people;
        }

        $nucleic_experiment_tubes = Tubes::whereHasMorph(
            'tubes_content',
            [NucleicAcids::class],
            function ($query) {
                $query->whereHasMorph(
                    'nucleic_content',
                    [Experiments::class],
                    function ($q) {
                        $q->whereHasMorph(
                            'experiments_content',
                            [NucleicAcids::class]
                        );
                    }
                );
            }
        )->with([
            'tubes_content',
            'tubes_content.protocols',
            'tubes_content.people',
            'tubes_content.projects',
            'tubes_content.nucleic_content',
            'tubes_content.nucleic_content.pathogens',
            'tubes_content.nucleic_content.protocols',
            'tubes_content.nucleic_content.experiments_content',
        ])->get();

        $tubes_service = app(TubesService::class);
        $tubes_data = $tubes_service->assign();

        $experiments_service = app(ExperimentsService::class);
        $experiments_data = $experiments_service->assign();

        $nucleic_types = $nucleic_acids->pluck('type')->unique()->sort()->values();

        $nucleic_methods_available = Protocols::with([
            'techniques',
            'studies',
        ])->whereHas('techniques', function ($query) {
            $query->where('type', 'Nucleic Acids Extraction and Purification');
        })->get();

        $experiments = Experiments::all();
        $labs_available = $this->laboratories_by_country();

        // Define nucleic states for elution types
        $preservants = ['Glycerol', 'Elution buffer', 'Formaline', 'Water', 'Ethanol', 'PBS', 'RNAlater'];

        $organization_types = [
            'Government' => 'Government',
            'Non-Governmental Organization' => 'Non-Governmental Organization',
            'Private Company' => 'Private Company',
            'University' => 'University',
            'Research Institute' => 'Research Institute',
            'Research Station' => 'Research Station',
            'Wildlife Center' => 'Wildlife Center',
            'Clinic' => 'Veterinary Clinic',
            'Field Station' => 'Field Station',
        ];

        $location_types = [
            'Stand-up freezer' => 'Stand-up freezer',
            'Chest freezer' => 'Chest freezer',
            'Refrigerator' => 'Refrigerator',
            'Walk-in refrigerator' => 'Walk-in refrigerator',
            'Walk-in freezer' => 'Walk-in freezer',
            'Shelf' => 'Shelf',
            'Cabinet' => 'Cabinet',
            'Drawer' => 'Drawer',
        ];

        $protocols = Protocols::with([
            'techniques',
            'studies',
            'pathogens']
        )->whereHas('techniques', function ($q) {
            $q->where('type', '==', 'Nucleic Acids Extraction and Purification');
        })->get();

        $techniques = Techniques::where('type', 'Nucleic Acids Extraction and Purification')->get();

        // Return data
        return [
            'nucleic_acids' => $nucleic_acids,
            'nucleic_types' => $nucleic_types,
            'human_tubes' => $tubes_data['human_tubes'],
            'animal_tubes' => $tubes_data['animal_tubes'],
            'environment_tubes' => $tubes_data['environment_tubes'],
            'parasite_tubes' => $tubes_data['parasite_tubes'],
            'culture_tubes' => $tubes_data['culture_tubes'],
            'pool_tubes' => $tubes_data['pool_tubes'],
            'nucleic_tubes' => $tubes_data['nucleic_tubes'],
            'nucleic_experiment_tubes' => $nucleic_experiment_tubes,
            'experiments_nucleic' => $experiments_data['experiments_nucleic'],
            'nucleic_methods_available' => $nucleic_methods_available,
            'people' => $people,
            'experiments' => $experiments,
            'preservants' => $preservants,
            'laboratories' => Laboratories::all(),
            'labs_available' => $labs_available,
            'organizations' => Organizations::all(),
            'countries' => Countries::all(),
            'laboratories' => Laboratories::all(),
            'organization_types' => $organization_types,
            'location_types' => $location_types,
            'protocols' => $protocols,
            'techniques' => $techniques,
        ];
    }

    public function dataForCreate(): array
    {
        $projectId = session('selected_project_id');

        $selectedHumanTubeIds = array_values(array_filter((array) old('human_tube_id', [])));
        $selectedAnimalTubeIds = array_values(array_filter((array) old('animal_tube_id', [])));
        $selectedEnvironmentTubeIds = array_values(array_filter((array) old('environment_tube_id', [])));
        $selectedParasiteTubeIds = array_values(array_filter((array) old('parasite_tube_id', [])));
        $selectedCultureTubeIds = array_values(array_filter((array) old('culture_tube_id', [])));
        $selectedPoolTubeIds = array_values(array_filter((array) old('pool_tube_id', [])));
        $selectedExperimentIds = array_values(array_filter((array) old('experiment_id', [])));

        $nucleicTypes = $this->nucleicTypesWithDefaults();

        $nucleicMethodsAvailable = Protocols::with(['techniques', 'studies', 'pathogens'])
            ->whereHas('techniques', function ($query) {
                $query->where('type', 'Nucleic Acids Extraction and Purification');
            })
            ->get();

        $labsAvailable = $this->laboratories_by_country();

        $people = Projects::find($projectId)?->people ?? collect();

        $organizations = Organizations::query()->get(['id', 'name']);
        $countries = Countries::query()->get(['id', 'name']);

        $techniques = Techniques::query()
            ->where('type', 'Nucleic Acids Extraction and Purification')
            ->get(['id', 'name', 'type']);

        $organization_types = [
            'Government' => 'Government',
            'Non-Governmental Organization' => 'Non-Governmental Organization',
            'Private Company' => 'Private Company',
            'University' => 'University',
            'Research Institute' => 'Research Institute',
            'Research Station' => 'Research Station',
            'Wildlife Center' => 'Wildlife Center',
            'Clinic' => 'Veterinary Clinic',
            'Field Station' => 'Field Station',
        ];

        $location_types = [
            'Stand-up freezer' => 'Stand-up freezer',
            'Chest freezer' => 'Chest freezer',
            'Refrigerator' => 'Refrigerator',
            'Walk-in refrigerator' => 'Walk-in refrigerator',
            'Walk-in freezer' => 'Walk-in freezer',
            'Shelf' => 'Shelf',
            'Cabinet' => 'Cabinet',
            'Drawer' => 'Drawer',
        ];

        $protocols = Protocols::query()
            ->whereHas('techniques', function ($q) {
                $q->where('type', 'Nucleic Acids Extraction and Purification');
            })
            ->with(['techniques:id,name,type'])
            ->get(['id', 'name']);

        return [
            'nucleic_types' => $nucleicTypes,
            'nucleic_methods_available' => $nucleicMethodsAvailable,
            'protocol_lookup_rows' => LookupTableData::protocols($nucleicMethodsAvailable),
            'laboratory_lookup_rows' => LookupTableData::laboratories(),
            'labs_available' => $labsAvailable,
            'people' => $people,
            'organizations' => $organizations,
            'countries' => $countries,
            'protocols' => $protocols,
            'techniques' => $techniques,
            'organization_types' => $organization_types,
            'location_types' => $location_types,

            'selected_human_tubes' => $selectedHumanTubeIds
                ? Tubes::query()->where('projects_id', $projectId)->whereIn('id', $selectedHumanTubeIds)->get(['id', 'code'])
                : collect(),
            'selected_animal_tubes' => $selectedAnimalTubeIds
                ? Tubes::query()->where('projects_id', $projectId)->whereIn('id', $selectedAnimalTubeIds)->get(['id', 'code'])
                : collect(),
            'selected_environment_tubes' => $selectedEnvironmentTubeIds
                ? Tubes::query()->where('projects_id', $projectId)->whereIn('id', $selectedEnvironmentTubeIds)->get(['id', 'code'])
                : collect(),
            'selected_parasite_tubes' => $selectedParasiteTubeIds
                ? Tubes::query()->where('projects_id', $projectId)->whereIn('id', $selectedParasiteTubeIds)->get(['id', 'code'])
                : collect(),
            'selected_culture_tubes' => $selectedCultureTubeIds
                ? Tubes::query()->where('projects_id', $projectId)->whereIn('id', $selectedCultureTubeIds)->get(['id', 'code'])
                : collect(),
            'selected_pool_tubes' => $selectedPoolTubeIds
                ? Tubes::query()->where('projects_id', $projectId)->whereIn('id', $selectedPoolTubeIds)->get(['id', 'code'])
                : collect(),
            'selected_experiments' => $selectedExperimentIds
                ? Experiments::query()->where('projects_id', $projectId)->whereIn('id', $selectedExperimentIds)->get(['id', 'code'])
                : collect(),
        ];
    }

    private function nucleicTypesWithDefaults()
    {
        $databaseTypes = NucleicAcids::query()
            ->whereNotNull('type')
            ->pluck('type');

        return collect(self::DEFAULT_NUCLEIC_TYPES)
            ->merge($databaseTypes)
            ->map(static fn ($type) => is_string($type) ? trim($type) : '')
            ->filter()
            ->unique(static fn (string $type) => mb_strtolower($type))
            ->sort(static fn (string $a, string $b) => strcasecmp($a, $b))
            ->values();
    }
}
