<?php

namespace App\Services;

use App\Models\Countries;
use App\Models\EnvironmentSamples;
use App\Models\EnvironmentSampleTypes;
use App\Models\Laboratories;
use App\Models\Locations;
use App\Models\Organizations;
use App\Models\People;
use App\Models\Projects;
use App\Models\SamplingSites;
use App\Support\LookupTableData;

class EnvironmentSamplesService
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

    public function sampling_sites_by_country()
    {
        // Retrieve sampling sites
        $sampling_sites = SamplingSites::with('countries', 'organization')->get();

        // Initialize an array to organize sites by country
        $sites_by_country = [];

        // Group sampling sites by country
        foreach ($sampling_sites as $site) {
            $country = $site->countries->name ?? 'Unknown country';
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
        $isGuestMode = $this->isGuestMode();
        $projectId = $this->projectId;

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            $environment_samples = EnvironmentSamples::whereHas('tubes', function ($query) {
                $query->where('is_private', 0);
            })->with([
                'sampling_sites',
                'environment_sample_types',
                'people',
                'locations',
                'projects',
                'tubes',
            ])->get();

            $people = People::all();

        } else {
            $environment_samples = EnvironmentSamples::with([
                'sampling_sites',
                'environment_sample_types',
                'people',
                'locations',
                'projects',
                'tubes',
            ])->where('projects_id', $this->projectId)
                ->get();

            $people = Projects::find($this->projectId)->people;
        }

        $environment_sample_types = EnvironmentSampleTypes::all();
        $locations = Locations::all();
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

        // Get additional data for the dashboard
        $additionalData = [
            'totalEnvironmentSamples' => $this->getTotalEnvironmentSamples($projectId, $isGuestMode),
            'environmentSampleTypes' => $this->getEnvironmentSampleTypes($projectId, $isGuestMode),
            'samplingSites' => $this->getSamplingSites($projectId, $isGuestMode),
            'collectors' => $this->getCollectors($projectId, $isGuestMode),
            'environment_sample_types' => $environment_sample_types,
            'locations' => $locations,
            'location_lookup_rows' => LookupTableData::locations(),
            'sampling_site_lookup_rows' => LookupTableData::samplingSites(),
            'sampling_sites_available' => $sampling_sites_available,
            'people' => $people,
            'location_types' => $location_types,
            'organization_types' => $organization_types,
            'site_types' => $site_types,
            'organizations' => Organizations::all(),
            'countries' => Countries::all(),
            'labs_available' => $labs_available,
            'environment_samples' => $environment_samples,
        ];

        return $additionalData;
    }

    private function getTotalEnvironmentSamples($projectId, $isGuestMode)
    {
        $query = EnvironmentSamples::query();

        if ($isGuestMode) {
            $query->whereHas('tubes', function ($q) {
                $q->where('is_private', false);
            });
        } else {
            $query->where('projects_id', $projectId);
        }

        return $query->count();
    }

    private function getEnvironmentSampleTypes($projectId, $isGuestMode)
    {
        $query = EnvironmentSampleTypes::withCount(['environment_samples' => function ($q) use ($projectId, $isGuestMode) {
            if ($isGuestMode) {
                $q->whereHas('tubes', function ($tq) {
                    $tq->where('is_private', false);
                });
            } else {
                $q->where('projects_id', $projectId);
            }
        }]);

        return $query->get();
    }

    private function getSamplingSites($projectId, $isGuestMode)
    {
        $query = EnvironmentSamples::with(['sampling_sites'])
            ->select('sampling_sites_id')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('sampling_sites_id');

        if ($isGuestMode) {
            $query->whereHas('tubes', function ($q) {
                $q->where('is_private', false);
            });
        } else {
            $query->where('projects_id', $projectId);
        }

        return $query->get();
    }

    private function getCollectors($projectId, $isGuestMode)
    {
        $query = EnvironmentSamples::with(['people'])
            ->select('people_id')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('people_id');

        if ($isGuestMode) {
            $query->whereHas('tubes', function ($q) {
                $q->where('is_private', false);
            });
        } else {
            $query->where('projects_id', $projectId);
        }

        return $query->get();
    }
}
