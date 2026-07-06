<?php

namespace App\Services;

use App\Models\AnimalSamples;
use App\Models\Countries;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\ParasiteSamples;
use App\Models\People;
use App\Models\Pools;
use App\Models\Projects;
use App\Models\Tubes;
use App\Support\LookupTableData;

class CulturesService
{
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
        // Retrieve laboratories with country information
        $laboratories = Laboratories::with('countries')->get();

        // Initialize an array to organize labs by country
        $labs_by_country = [];

        // Group laboratories by country
        foreach ($laboratories as $lab) {
            $country = $lab->countries->name ?? 'Unknown country';
            $name = $lab->name ?? '';

            if ($name) {
                $labs_by_country[$country][] = [
                    'name' => $name,
                    'type' => 'laboratory',
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

    public function getCultureTypes(): array
    {
        return [
            'Solid',
            'Semi-solid',
            'Liquid',
            'Biphasic',
            'Cell culture',
        ];
    }

    public function getMediums(): array
    {
        return [
            'Brain Heart Infusion broth (BHI)',
            'Blood Agar',
            'MacConkey Agar',
            'Chocolate agar',
            'Tryptic Soy Agar (TSA)',
            'Brucella selective supplement',
            'CITA',
            'Löwenstein-Jensen',
            'Middlebrook 7H10 / 7H11 Agar',
            'Sabouraud Dextrose Agar (SDA)',
            'Potato Dextrose Agar (PDA)',
            'Chromogenic Agar for Candida spp.',
            'NNN Medium',
            'Liver Infusion Tryptose (LIT) Medium',
            'Biphasic Novy-MacNeal-Nicolle (NNN) Medium',
            'DMEM',
            'RPMI-1640',
            'MEM',
            "Ham's F-12",
            "Leibovitz's L-15",
            'Sabouraud Dextrose Agar',
            'Chocolate Agar',
            'Mueller Hinton Agar',
            'Thayer Martin Agar',
            'Lowenstein Jensen Medium',
            'Middlebrook 7H10 Agar',
            'Brain Heart Infusion Broth',
            'Tryptic Soy Broth',
        ];
    }

    public function getAtmospheres(): array
    {
        return [
            'Air',
            'CO2 - 5%',
            'CO2 - 10%',
            'N2',
            'Mixed',
            'Aerobic',
            'Anaerobic',
            'Microaerophilic',
            'CO2 Enriched',
            'Capnophilic',
        ];
    }

    public function getIncubationTemps(): array
    {
        return [25, 30, 35, 37, 42];
    }

    /**
     * @param  list<array<int|string>|list<string>>  $lists
     * @return list<string>
     */
    public function mergeOptionLists(array ...$lists): array
    {
        $merged = [];

        foreach ($lists as $list) {
            foreach ($list as $item) {
                $value = trim((string) $item);
                if ($value !== '') {
                    $merged[$value] = $value;
                }
            }
        }

        $values = array_values($merged);
        sort($values, SORT_NATURAL | SORT_FLAG_CASE);

        return $values;
    }

    public function getExistingCultureTypes()
    {
        return Cultures::where('projects_id', $this->projectId)
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->pluck('type')
            ->unique()
            ->values()
            ->toArray();
    }

    public function getExistingMediums()
    {
        return Cultures::where('projects_id', $this->projectId)
            ->whereNotNull('medium')
            ->where('medium', '!=', '')
            ->pluck('medium')
            ->unique()
            ->values()
            ->toArray();
    }

    public function getExistingAtmospheres()
    {
        return Cultures::where('projects_id', $this->projectId)
            ->whereNotNull('athmosphere')
            ->where('athmosphere', '!=', '')
            ->pluck('athmosphere')
            ->unique()
            ->values()
            ->toArray();
    }

    public function getExistingIncubationTemps()
    {
        return Cultures::where('projects_id', $this->projectId)
            ->whereNotNull('incubation_temp')
            ->where('incubation_temp', '!=', '')
            ->pluck('incubation_temp')
            ->unique()
            ->values()
            ->toArray();
    }

    public function getAvailableCultureCodes()
    {
        return Cultures::where('projects_id', $this->projectId)
            ->pluck('code')
            ->toArray();
    }

    public function getAvailableContentCodes()
    {
        $codes = [];

        // Get animal sample codes
        $animalCodes = AnimalSamples::where('projects_id', $this->projectId)->pluck('code')->toArray();
        $codes = array_merge($codes, $animalCodes);

        // Get human sample codes
        $humanCodes = HumanSamples::where('projects_id', $this->projectId)->pluck('code')->toArray();
        $codes = array_merge($codes, $humanCodes);

        // Get environment sample codes
        $environmentCodes = EnvironmentSamples::where('projects_id', $this->projectId)->pluck('code')->toArray();
        $codes = array_merge($codes, $environmentCodes);

        // Get parasite sample codes
        $parasiteCodes = ParasiteSamples::where('projects_id', $this->projectId)->pluck('code')->toArray();
        $codes = array_merge($codes, $parasiteCodes);

        // Get pool codes
        $poolCodes = Pools::where('projects_id', $this->projectId)->pluck('code')->toArray();
        $codes = array_merge($codes, $poolCodes);

        return $codes;
    }

    public function assign()
    {
        $tubes_service = app(TubesService::class);
        $tubes_data = $tubes_service->assign();

        $cultures = Cultures::with(
            'cultures_content',
            'parent',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
        )->where('projects_id', $this->projectId)->get();

        $cultures_humans = Cultures::whereHas('cultures_content', function ($query) {
            $query->where('cultures_content_type', HumanSamples::class);
        })->with(
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'cultures_content',
            'cultures_content.humans',
            'cultures_content.sample_types',
            'cultures_content.sampling_sites',
            'cultures_content.sampling_sites.countries',
        )->where('projects_id', $this->projectId)
            ->get();

        $cultures_animals = Cultures::whereHas('cultures_content', function ($query) {
            $query->where('cultures_content_type', AnimalSamples::class);
        })->with(
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'cultures_content',
            'cultures_content.animals',
            'cultures_content.animals.animal_species',
            'cultures_content.sample_types',
            'cultures_content.sampling_sites',
            'cultures_content.sampling_sites.countries',
        )->where('projects_id', $this->projectId)
            ->get();

        $cultures_environment = Cultures::whereHas('cultures_content', function ($query) {
            $query->where('cultures_content_type', EnvironmentSamples::class);
        })->with(
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'cultures_content',
            'cultures_content.environment_sample_types',
            'cultures_content.sampling_sites',
            'cultures_content.sampling_sites.countries',
        )->where('projects_id', $this->projectId)
            ->get();

        $cultures_parasites = Cultures::whereHas('cultures_content', function ($query) {
            $query->where('cultures_content_type', ParasiteSamples::class);
        })->with(
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'cultures_content',
            'cultures_content.parasites.parasite_species',
            'cultures_content.parasites.parasites_origin',
            'cultures_content.parasites.parasites_origin.sampling_sites',
            'cultures_content.parasites.parasites_origin.sampling_sites.countries',
        )->where('projects_id', $this->projectId)
            ->get();

        $cultures_pools = Cultures::whereHas('cultures_content', function ($query) {
            $query->where('cultures_content_type', Pools::class);
        })->with(
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'cultures_content',
        )->where('projects_id', $this->projectId)
            ->get();

        // Get stratified options for select inputs
        $labs_available = $this->laboratories_by_country();

        // Return data
        return [
            'cultures' => $cultures,
            'cultures_humans' => $cultures_humans,
            'cultures_animals' => $cultures_animals,
            'cultures_environment' => $cultures_environment,
            'cultures_parasites' => $cultures_parasites,
            'cultures_pools' => $cultures_pools,
            'laboratories' => Laboratories::with('countries')->get(),
            'labs_available' => $labs_available,
            'culture_types' => $this->mergeOptionLists($this->getCultureTypes(), $this->getExistingCultureTypes()),
            'mediums' => $this->mergeOptionLists($this->getMediums(), $this->getExistingMediums()),
            'atmospheres' => $this->mergeOptionLists($this->getAtmospheres(), $this->getExistingAtmospheres()),
            'incubation_temps' => $this->mergeOptionLists(
                array_map('strval', $this->getIncubationTemps()),
                array_map('strval', $this->getExistingIncubationTemps())
            ),
            'available_culture_codes' => $this->getAvailableCultureCodes(),
            'available_content_codes' => $this->getAvailableContentCodes(),
            'current_project_id' => $this->projectId,
            'human_tubes' => $tubes_data['human_tubes'],
            'animal_tubes' => $tubes_data['animal_tubes'],
            'environment_tubes' => $tubes_data['environment_tubes'],
            'parasite_tubes' => $tubes_data['parasite_tubes'],
            'nucleic_tubes' => $tubes_data['nucleic_tubes'],
            'pool_tubes' => $tubes_data['pool_tubes'],
            'people' => $this->isGuestMode() ? People::all() : Projects::find($this->projectId)->people,
        ];
    }

    public function dataForCreate(): array
    {
        $projectId = session('selected_project_id');

        $selectedHumanTubeIds = array_values(array_filter((array) old('human_tube_id', [])));
        $selectedAnimalTubeIds = array_values(array_filter((array) old('animal_tube_id', [])));
        $selectedEnvironmentTubeIds = array_values(array_filter((array) old('environment_tube_id', [])));
        $selectedParasiteTubeIds = array_values(array_filter((array) old('parasite_tube_id', [])));
        $selectedPoolTubeIds = array_values(array_filter((array) old('pool_tube_id', [])));
        $selectedCultureIds = array_values(array_filter((array) old('culture_id', [])));
        $selectedNucleicTubeIds = array_values(array_filter((array) old('nucleic_tube_id', [])));

        $labsAvailable = $this->laboratories_by_country();
        $people = Projects::find($projectId)?->people ?? collect();

        return [
            'labs_available' => $labsAvailable,
            'laboratory_lookup_rows' => LookupTableData::laboratories(),
            'people' => $people,
            'culture_type_options' => $this->mergeOptionLists($this->getCultureTypes(), $this->getExistingCultureTypes()),
            'medium_options' => $this->mergeOptionLists($this->getMediums(), $this->getExistingMediums()),
            'atmosphere_options' => $this->mergeOptionLists($this->getAtmospheres(), $this->getExistingAtmospheres()),

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
            'selected_pool_tubes' => $selectedPoolTubeIds
                ? Tubes::query()->where('projects_id', $projectId)->whereIn('id', $selectedPoolTubeIds)->get(['id', 'code'])
                : collect(),
            'selected_nucleic_tubes' => $selectedNucleicTubeIds
                ? Tubes::query()->where('projects_id', $projectId)->whereIn('id', $selectedNucleicTubeIds)->get(['id', 'code'])
                : collect(),
            'selected_cultures' => $selectedCultureIds
                ? Cultures::query()->where('projects_id', $projectId)->whereIn('id', $selectedCultureIds)->get(['id', 'code'])
                : collect(),
        ];
    }
}
