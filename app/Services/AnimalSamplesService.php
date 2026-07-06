<?php

namespace App\Services;

use App\Models\AnimalHealth;
use App\Models\AnimalMedication;
use App\Models\Animals;
use App\Models\AnimalSamples;
use App\Models\AnimalSpecies;
use App\Models\AnimalVaccination;
use App\Models\ClinicalSigns;
use App\Models\Countries;
use App\Models\Humans;
use App\Models\Laboratories;
use App\Models\Lesions;
use App\Models\Locations;
use App\Models\Organizations;
use App\Models\People;
use App\Models\Projects;
use App\Models\SampleTypes;
use App\Models\SamplingSites;
use App\Support\LookupTableData;

class AnimalSamplesService
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

    public function species_by_family()
    {

        $animal_species = AnimalSpecies::all();

        // Initialize an array to organize species by family
        $species_by_family = [];

        // Group animal species by family
        foreach ($animal_species as $species) {
            $family = $species['family'] ?? 'Unknown Family';
            $name_common = $species['name_common'] ?? '';
            $name_scientific = $species['name_scientific'] ?? ''; // Assuming scientific name is stored as 'name_scientific'

            if ($name_common) {
                // Store each species with both common and scientific names
                $species_by_family[$family][] = [
                    'common' => $name_common,
                    'scientific' => $name_scientific,
                ];
            }
        }

        // Sort families alphabetically
        ksort($species_by_family);

        // Sort species alphabetically within each family
        foreach ($species_by_family as $family => $species_list) {
            usort($species_by_family[$family], function ($a, $b) {
                return strcmp($a['common'], $b['common']);
            });
        }

        return $species_by_family;
    }

    public function sampling_sites_by_country()
    {
        // Retrieve sampling sites
        $sampling_sites = SamplingSites::with('countries', 'organization')->get();

        // Initialize an array to organize sites by country
        $sites_by_country = [];

        // Group sampling sites by country
        foreach ($sampling_sites as $site) {
            $country = $site->countries?->name ?? 'Unknown country';
            $name = $site['name'] ?? '';

            if ($name) {
                $sites_by_country[$country][] = [
                    'name' => $name,
                ];
            }
        }

        // Sort countries alphabetically
        ksort($sites_by_country);

        // Sort sites alphabetically within each country
        foreach ($sites_by_country as $country => $sites_list) {
            usort($sites_by_country[$country], function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
        }

        return $sites_by_country;
    }

    public function laboratories_by_country()
    {
        // Retrieve laboratories
        $laboratories = Laboratories::with('countries', 'organization')->get();

        // Initialize an array to organize labs by country
        $labs_by_country = [];

        // Group laboratories by country
        foreach ($laboratories as $lab) {
            $country = $lab->countries?->name ?? 'Unknown country';
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

    public function dataForCreate(): array
    {
        $location_types = [
            'Hospital' => 'Hospital',
            'Clinic' => 'Clinic',
            'Natural Park' => 'Natural Park',
            'Farm' => 'Farm',
            'Zoo' => 'Zoo',
            'Sanctuary' => 'Sanctuary',
            'National Park' => 'National Park',
            'Private Reserve' => 'Private Reserve',
            'Game Reserve' => 'Game Reserve',
            'Conservation Area' => 'Conservation Area',
            'Laboratory' => 'Laboratory',
            'Research Station' => 'Research Station',
            'Wildlife Center' => 'Wildlife Center',
            'Veterinary Clinic' => 'Veterinary Clinic',
            'Field Station' => 'Field Station',
        ];

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

        $site_types = [
            'Hospital' => 'Hospital',
            'Clinic' => 'Clinic',
            'Natural Park' => 'Natural Park',
            'Farm' => 'Farm',
            'Zoo' => 'Zoo',
            'Sanctuary' => 'Sanctuary',
            'National Park' => 'National Park',
            'Private Reserve' => 'Private Reserve',
            'Game Reserve' => 'Game Reserve',
            'Conservation Area' => 'Conservation Area',
            'Laboratory' => 'Laboratory',
            'Research Station' => 'Research Station',
            'Wildlife Center' => 'Wildlife Center',
            'Veterinary Clinic' => 'Veterinary Clinic',
            'Field Station' => 'Field Station',
        ];

        $species_by_family = $this->species_by_family();
        $sampling_sites_available = $this->sampling_sites_by_country();
        $labs_available = $this->laboratories_by_country();

        $people = collect();
        if ($this->projectId) {
            $people = Projects::find($this->projectId)?->people ?? collect();
        }

        $unique_reasons = AnimalSamples::query()
            ->when($this->projectId, fn ($q) => $q->where('projects_id', $this->projectId))
            ->whereNotNull('immobilization_reason')
            ->distinct()
            ->orderBy('immobilization_reason')
            ->pluck('immobilization_reason')
            ->values();

        $storageStateDefaults = [
            'No preservant',
            'Formalin',
            'RNAlater',
        ];
        $storageStateDataset = AnimalSamples::query()
            ->when($this->projectId, fn ($q) => $q->where('projects_id', $this->projectId))
            ->whereNotNull('storage_state')
            ->distinct()
            ->orderBy('storage_state')
            ->pluck('storage_state')
            ->filter(fn ($v) => filled($v))
            ->values()
            ->all();
        $storage_state_options = array_values(array_unique(array_merge($storageStateDefaults, $storageStateDataset)));

        $ethnicities = [
            'African' => 'African',
            'Asian' => 'Asian',
            'Caucasian' => 'Caucasian',
            'Hispanic' => 'Hispanic',
            'Middle Eastern' => 'Middle Eastern',
            'Native American' => 'Native American',
            'Pacific Islander' => 'Pacific Islander',
            'South Asian' => 'South Asian',
            'West Asian' => 'West Asian',
            'Mixed' => 'Mixed',
        ];

        $occupations = [
            'Student' => 'Student',
            'Teacher' => 'Teacher',
            'Doctor' => 'Doctor',
            'Nurse' => 'Nurse',
            'Engineer' => 'Engineer',
            'Lawyer' => 'Lawyer',
            'Farmer' => 'Farmer',
            'Unemployed' => 'Unemployed',
        ];

        // Get project information for animal code generation (used in nested animal registration modal)
        $project_code = '';
        $current_max_animal_serial = 0;
        if ($this->projectId) {
            $project = Projects::find($this->projectId);
            if ($project) {
                $project_code = $project->code;

                $existingAnimalCodes = Animals::where('projects_id', $this->projectId)
                    ->where('code', 'like', $project_code.'-AN-%')
                    ->pluck('code');

                $usedNumbers = $existingAnimalCodes->map(function ($code) {
                    preg_match('/-AN-(\d+)$/', $code, $matches);

                    return isset($matches[1]) ? (int) $matches[1] : null;
                })->filter()->sort()->values();

                $newSerial = 1;
                foreach ($usedNumbers as $num) {
                    if ($num != $newSerial) {
                        break;
                    }
                    $newSerial++;
                }

                $current_max_animal_serial = $newSerial - 1;
            }
        }

        return [
            // Form selects (lightweight; no full animals list)
            'sample_types' => SampleTypes::query()->orderBy('name')->get(),
            'locations' => Locations::all(),
            'location_lookup_rows' => LookupTableData::locations(),
            'sampling_site_lookup_rows' => LookupTableData::samplingSites(),
            'sampling_sites_available' => $sampling_sites_available,
            'unique_reasons' => $unique_reasons,
            'storage_state_options' => $storage_state_options,
            'people' => $people,

            // Nested modals (create animals / sites / locations)
            'species_by_family' => $species_by_family,
            'animal_species' => AnimalSpecies::all(),
            'humans' => Humans::when($this->projectId, fn ($q) => $q->where('projects_id', $this->projectId))
                ->orderBy('first_name')
                ->get(),
            'organizations' => Organizations::orderBy('name')->get(),
            'countries' => Countries::orderBy('name')->get(),
            'labs_available' => $labs_available,
            'location_types' => $location_types,
            'organization_types' => $organization_types,
            'site_types' => $site_types,
            'project_code' => $project_code,
            'current_max_animal_serial' => $current_max_animal_serial,
            'ethnicities' => $ethnicities,
            'occupations' => $occupations,
        ];
    }

    public function assign()
    {
        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {

            $animal_samples = AnimalSamples::whereHas('tubes', function ($query) {
                $query->where('is_private', 0);
            })->with([
                'animals',
                'animals.animal_species',
                'animals.owner',
                'sample_types',
                'sampling_sites',
                'people',
                'locations',
                'projects',
            ])->get();

            $animals = Animals::with([
                'animal_species',
                'owner',
                'projects',
            ])->orderBy('created_at', 'desc')
                ->get();

            $animal_health = AnimalHealth::with([
                'animals',
                'animals.animal_species',
                'clinical_signs',
                'lesions',

            ])->orderBy('created_at', 'desc')->get();

            $people = People::all();
        } else {

            $animal_samples = AnimalSamples::with([
                'animals',
                'animals.animal_species',
                'animals.owner',
                'sample_types',
                'sampling_sites',
                'people',
                'locations',
                'projects',
            ])->where('projects_id', $this->projectId)->orderBy('created_at', 'desc')
                ->get();

            $animals = Animals::whereHasMorph('owner', [Humans::class, Organizations::class,
            ])->with([
                'animal_species',
                'owner',
                'projects',
            ])->where('projects_id', $this->projectId)
                ->orderBy('created_at', 'desc')
                ->get();

            $animal_health = AnimalHealth::with([
                'animals',
                'animals.animal_species',
                'clinical_signs',
                'lesions',

            ])->whereHas('animals', function ($query) {
                $query->where('projects_id', $this->projectId);
            })->orderBy('created_at', 'desc')->get();

            $people = Projects::find($this->projectId)->people;

            // Check if user can edit (admin and editor can edit, viewer cannot)
            $canEdit = true;

            $project = Projects::find($this->projectId);

            if ($project && $project->pivot && $project->pivot->permission === 'viewer') {
                $canEdit = false;
            }
        }

        $ethnicities = [
            'African' => 'African',
            'Asian' => 'Asian',
            'Caucasian' => 'Caucasian',
            'Hispanic' => 'Hispanic',
            'Middle Eastern' => 'Middle Eastern',
            'Native American' => 'Native American',
            'Pacific Islander' => 'Pacific Islander',
            'South Asian' => 'South Asian',
            'West Asian' => 'West Asian',
            'Mixed' => 'Mixed',
        ];

        $occupations = [
            'Student' => 'Student',
            'Teacher' => 'Teacher',
            'Doctor' => 'Doctor',
            'Nurse' => 'Nurse',
            'Engineer' => 'Engineer',
            'Lawyer' => 'Lawyer',
            'Farmer' => 'Farmer',
            'Unemployed' => 'Unemployed',
        ];

        // Get animal samples with unique animal ids
        $animals_existing = $animal_samples->unique('animals_id')->values();
        $animal_species_existing = $animal_samples->unique('animals.animal_species_id')->values();
        $sampling_sites_existing = $animal_samples->unique('sampling_sites_id')->values();
        $sample_types_existing = $animal_samples->unique('sample_types_id')->values();
        $locations_existing = $animal_samples->unique('locations_id')->values();
        $health_statuses_existing = $animal_health->pluck('health_status')->unique()->values();
        $check_types_existing = $animal_health->pluck('check_type')->unique()->values();

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

        $site_types = [
            'Hospital' => 'Hospital',
            'Clinic' => 'Clinic',
            'Natural Park' => 'Natural Park',
            'Farm' => 'Farm',
            'Zoo' => 'Zoo',
            'Sanctuary' => 'Sanctuary',
            'National Park' => 'National Park',
            'Private Reserve' => 'Private Reserve',
            'Game Reserve' => 'Game Reserve',
            'Conservation Area' => 'Conservation Area',
            'Laboratory' => 'Laboratory',
            'Research Station' => 'Research Station',
            'Wildlife Center' => 'Wildlife Center',
            'Veterinary Clinic' => 'Veterinary Clinic',
            'Field Station' => 'Field Station',
        ];

        // Get stratified options for select inputs
        $species_by_family = $this->species_by_family();
        $sampling_sites_available = $this->sampling_sites_by_country();
        $labs_available = $this->laboratories_by_country();

        // Get project information for animal code generation
        $project = null;
        $project_code = '';
        $current_max_animal_serial = 0;

        if ($this->projectId) {
            $project = Projects::find($this->projectId);
            if ($project) {
                $project_code = $project->code;

                // Get the current max serial number for animals in this project
                $existingAnimalCodes = Animals::where('projects_id', $this->projectId)
                    ->where('code', 'like', $project_code.'-AN-%')
                    ->pluck('code');

                $usedNumbers = $existingAnimalCodes->map(function ($code) {
                    preg_match('/-AN-(\d+)$/', $code, $matches);

                    return isset($matches[1]) ? (int) $matches[1] : null;
                })->filter()->sort()->values();

                $newSerial = 1;
                foreach ($usedNumbers as $num) {
                    if ($num != $newSerial) {
                        break;
                    }
                    $newSerial++;
                }

                $current_max_animal_serial = $newSerial - 1; // This represents the last used serial
            }
        }

        // Return data
        return [
            'animal_samples' => $animal_samples,
            'animals' => $animals,
            'animal_species' => AnimalSpecies::all(),
            'sampling_sites' => SamplingSites::all(),
            'laboratories' => Laboratories::all(),
            'sample_types' => collect(SampleTypes::all())->sortBy('name')->values(),
            'locations' => Locations::all(),
            'location_types' => $location_types,
            'organization_types' => $organization_types,
            'animals_existing' => $animals_existing,
            'animal_species_existing' => $animal_species_existing,
            'sampling_sites_existing' => $sampling_sites_existing,
            'sampling_sites_available' => $sampling_sites_available,
            'labs_available' => $labs_available,
            'sample_types_existing' => $sample_types_existing,
            'species_by_family' => $species_by_family,
            'unique_reasons' => $animal_samples->pluck('immobilization_reason')->unique()->values(),
            'people' => $people,
            'humans' => Humans::where('projects_id', $this->projectId)->get(),
            'organizations' => Organizations::all(),
            'site_types' => $site_types,
            'countries' => Countries::all(),
            'current_project_id' => $this->projectId,
            'clinical_signs' => ClinicalSigns::orderBy('name')->get(),
            'lesions' => Lesions::orderBy('name')->get(),
            'health_statuses_existing' => $health_statuses_existing,
            'check_types_existing' => $check_types_existing,
            'animal_health' => $animal_health,
            'animal_medications' => AnimalMedication::with('animals', 'people')->orderBy('medication_name')->get(),
            'animal_vaccinations' => AnimalVaccination::with('animals', 'people')->orderBy('vaccination_name')->get(),
            'project_code' => $project_code,
            'current_max_animal_serial' => $current_max_animal_serial,
            'ethnicities' => $ethnicities,
            'occupations' => $occupations,

        ];
    }
}
