<?php

namespace App\Services;

use App\Models\AnimalSamples;
use App\Models\Countries;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\Locations;
use App\Models\Organizations;
use App\Models\Parasites;
use App\Models\ParasiteSamples;
use App\Models\ParasiteSampleTypes;
use App\Models\ParasiteSpecies;
use App\Models\People;
use App\Models\Projects;
use App\Models\SamplingSites;
use App\Models\Tubes;
use App\Support\LookupTableData;

class ParasiteSamplesService
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

        $parasite_species = ParasiteSpecies::all();

        // Initialize an array to organize species by family
        $species_by_family = [];

        // Group parasite species by family
        foreach ($parasite_species as $species) {
            $family = $species['family'] ?? 'Unknown Family';
            $name_scientific = $species['name_scientific'] ?? ''; // Assuming scientific name is stored as 'name_scientific'

            $species_by_family[$family][] = [
                'scientific' => $name_scientific,
            ];
        }

        // Sort families alphabetically
        ksort($species_by_family);

        // Sort species alphabetically within each family
        foreach ($species_by_family as $family => $species_list) {
            usort($species_by_family[$family], function ($a, $b) {
                return strcmp($a['scientific'], $b['scientific']);
            });
        }

        return $species_by_family;
    }

    public function sampling_sites_by_country()
    {
        // Retrieve sampling sites
        $sampling_sites = SamplingSites::all();

        // Initialize an array to organize sites by country
        $sites_by_country = [];

        // Group sampling sites by country
        foreach ($sampling_sites as $site) {
            $country = $site['country'] ?? 'Unknown country';
            $name = $site['name'] ?? '';

            if ($name) {
                $sites_by_country[$country][] = [
                    'name' => $name,
                    'type' => 'sampling_site',
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

    public function assign()
    {
        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {

            $parasite_samples = ParasiteSamples::whereHas(
                'parasites',
                function ($query) {
                    $query->whereHasMorph(
                        'parasites_origin',
                        [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class]
                    );
                }
            )->with([
                'parasites',
                'parasites.parasite_species',
                'parasites.parasites_origin',
                'parasites.parasites_origin.sampling_sites',
                'parasite_sample_types',
                'people',
                'projects',
                'tubes',
            ])->whereHas('tubes', function ($query) {
                $query->where('is_private', 0);
            })->get();

            $parasite_human_samples = ParasiteSamples::whereHas(
                'parasites',
                function ($query) {
                    $query->whereHasMorph(
                        'parasites_origin',
                        [HumanSamples::class]
                    );
                }
            )->with([
                'parasites',
                'parasites.parasite_species',
                'parasites.parasites_origin',
                'parasites.parasites_origin.humans',
                'parasites.parasites_origin.sampling_sites',
                'parasites.parasites_origin.people',
                'parasite_sample_types',
                'people',
                'projects',
                'tubes',
            ])->whereHas('tubes', function ($query) {
                $query->where('is_private', 0);
            })->get();

            $parasite_animal_samples = ParasiteSamples::whereHas(
                'parasites',
                function ($query) {
                    $query->whereHasMorph(
                        'parasites_origin',
                        [AnimalSamples::class]
                    );
                }
            )->with([
                'parasites',
                'parasites.parasite_species',
                'parasites.parasites_origin',
                'parasites.parasites_origin.animals',
                'parasites.parasites_origin.animals.animal_species',
                'parasites.parasites_origin.sampling_sites',
                'parasites.parasites_origin.people',
                'parasite_sample_types',
                'people',
                'projects',
                'tubes',
            ])->whereHas('tubes', function ($query) {
                $query->where('is_private', 0);
            })->get();

            $parasite_environment_samples = ParasiteSamples::whereHas(
                'parasites',
                function ($query) {
                    $query->whereHasMorph(
                        'parasites_origin',
                        [EnvironmentSamples::class]
                    );
                }
            )->with([
                'parasites',
                'parasites.parasite_species',
                'parasites.parasites_origin',
                'parasites.parasites_origin.environment_sample_types',
                'parasites.parasites_origin.sampling_sites',
                'parasites.parasites_origin.people',
                'parasite_sample_types',
                'people',
                'projects',
                'tubes',
            ])->whereHas('tubes', function ($query) {
                $query->where('is_private', 0);
            })->get();

            $people = People::all();
        } else {

            $parasite_samples = ParasiteSamples::whereHas(
                'parasites',
                function ($query) {
                    $query->whereHasMorph(
                        'parasites_origin',
                        [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class]
                    );
                }
            )->with([
                'parasites',
                'parasites.parasite_species',
                'parasites.parasites_origin',
                'parasites.parasites_origin.sampling_sites',
                'parasite_sample_types',
                'people',
                'projects',
            ])->where('projects_id', $this->projectId)
                ->get();

            $parasite_human_samples = ParasiteSamples::whereHas(
                'parasites',
                function ($query) {
                    $query->whereHasMorph(
                        'parasites_origin',
                        [HumanSamples::class]
                    );
                }
            )->with([
                'parasites',
                'parasites.parasite_species',
                'parasites.parasites_origin',
                'parasites.parasites_origin.humans',
                'parasites.parasites_origin.sampling_sites',
                'parasites.parasites_origin.people',
                'parasite_sample_types',
                'people',
                'projects',
            ])->where('projects_id', $this->projectId)
                ->get();

            $parasite_animal_samples = ParasiteSamples::whereHas(
                'parasites',
                function ($query) {
                    $query->whereHasMorph(
                        'parasites_origin',
                        [AnimalSamples::class]
                    );
                }
            )->with([
                'parasites',
                'parasites.parasite_species',
                'parasites.parasites_origin',
                'parasites.parasites_origin.animals',
                'parasites.parasites_origin.animals.animal_species',
                'parasites.parasites_origin.sampling_sites',
                'parasites.parasites_origin.people',
                'parasite_sample_types',
                'people',
                'projects',
            ])->where('projects_id', $this->projectId)
                ->get();

            $parasite_environment_samples = ParasiteSamples::whereHas(
                'parasites',
                function ($query) {
                    $query->whereHasMorph(
                        'parasites_origin',
                        [EnvironmentSamples::class]
                    );
                }
            )->with([
                'parasites',
                'parasites.parasite_species',
                'parasites.parasites_origin',
                'parasites.parasites_origin.environment_sample_types',
                'parasites.parasites_origin.sampling_sites',
                'parasites.parasites_origin.people',
                'parasite_sample_types',
                'people',
                'projects',
            ])->where('projects_id', $this->projectId)
                ->get();

            $people = Projects::find($this->projectId)->people;
        }

        $parasites = Parasites::whereHasMorph(
            'parasites_origin',
            [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class]
        )->with([
            'parasite_species',
            'parasites_origin',
            'people',
            'projects',
            'locations',
        ])->where('projects_id', $this->projectId)
            ->get();

        $parasite_tubes = Tubes::whereHasMorph(
            'tubes_content',
            [ParasiteSamples::class],
            function ($query) {
                $query->whereHas(
                    'parasites',
                    function ($query) {
                        $query->whereHasMorph(
                            'parasites_origin',
                            [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class]
                        );
                    }
                );
            }
        )->with(
            'tubes_content',
            'tubes_content.parasites',
            'tubes_content.parasites.parasite_species',
            'tubes_content.parasites.parasites_origin',
            'tubes_content.parasite_sample_types',
            'tubes_content.people',
            'tubes_content.projects'
        )->get();

        $human_samples = HumanSamples::with([
            'humans',
            'sample_types',
            'sampling_sites',
            'people',
            'locations',
            'projects',
        ])->where('projects_id', $this->projectId)
            ->whereHas('sample_types', function ($query) {
                $query->where('category', 'non_host_derived');
            })->orderBy('created_at', 'desc')->get();

        $animal_samples = AnimalSamples::with([
            'animals',
            'animals.animal_species',
            'sample_types',
            'sampling_sites',
            'people',
            'locations',
            'projects',
        ])->where('projects_id', $this->projectId)
            ->whereHas('sample_types', function ($query) {
                $query->where('category', 'non_host_derived');
            })->orderBy('created_at', 'desc')->get();

        $environment_samples = EnvironmentSamples::with([
            'environment_sample_types',
            'sampling_sites',
            'people',
            'projects',
        ])->where('projects_id', $this->projectId)
            ->whereHas('environment_sample_types', function ($query) {
                $query->where('category', 'Parasites');
            })->orderBy('created_at', 'desc')->get();

        // Get parasite samples with unique animal ids
        $parasites_existing = $parasite_samples->unique('parasites_id')->values();
        $parasite_species_existing = $parasite_samples->unique('parasites.parasite_species_id')->values();
        $sampling_sites_existing = $parasite_samples->unique('sampling_sites_id')->values();
        $sample_types_existing = $parasite_samples->unique('parasite_sample_types_id')->values();

        // Get stratified options for select inputs
        $species_by_family = $this->species_by_family();
        $sampling_sites_available = $this->sampling_sites_by_country();
        $labs_available = $this->laboratories_by_country();

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

        // Return data
        return [
            'parasite_samples' => $parasite_samples,
            'parasite_human_samples' => $parasite_human_samples,
            'parasite_animal_samples' => $parasite_animal_samples,
            'parasite_environment_samples' => $parasite_environment_samples,
            'parasites' => $parasites,
            'parasite_species' => ParasiteSpecies::all(),
            'sampling_sites' => SamplingSites::all(),
            'sample_types' => collect(ParasiteSampleTypes::all())->sortBy('name')->values(),
            'locations' => Locations::all(),
            'parasites_existing' => $parasites_existing,
            'parasite_species_existing' => $parasite_species_existing,
            'sampling_sites_existing' => $sampling_sites_existing,
            'sampling_sites_available' => $sampling_sites_available,
            'labs_available' => $labs_available,
            'sample_types_existing' => $sample_types_existing,
            'species_by_family' => $species_by_family,
            'people' => $people,
            'parasite_tubes' => $parasite_tubes,
            'animal_samples' => $animal_samples,
            'human_samples' => $human_samples,
            'environment_samples' => $environment_samples,
            'organizations' => Organizations::all(),
            'site_types' => $site_types,
            'countries' => Countries::all(),
            'laboratories' => Laboratories::all(),
            'organization_types' => $organization_types,
            'location_types' => $location_types,
        ];
    }

    public function dataForCreate(): array
    {
        $people = $this->projectId
            ? (Projects::find($this->projectId)?->people ?? collect())
            : collect();

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

        return [
            'parasite_species' => ParasiteSpecies::all(),
            'species_by_family' => $this->species_by_family(),
            'labs_available' => $this->laboratories_by_country(),
            'parasite_species_lookup_rows' => LookupTableData::parasiteSpecies(),
            'laboratory_lookup_rows' => LookupTableData::laboratories(),
            'people' => $people,
            'organizations' => Organizations::all(),
            'countries' => Countries::all(),
            'organization_types' => $organization_types,
        ];
    }
}
