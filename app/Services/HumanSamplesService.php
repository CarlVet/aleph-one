<?php

namespace App\Services;

use App\Models\Countries;
use App\Models\Humans;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\Locations;
use App\Models\Organizations;
use App\Models\People;
use App\Models\SampleTypes;
use App\Models\SamplingSites;
use App\Models\Tubes;
use App\Support\LookupTableData;

class HumanSamplesService
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
        // Retrieve sampling sites with country information
        $sampling_sites = SamplingSites::with('countries', 'organization')->get();

        // Initialize an array to organize sites by country
        $sites_by_country = [];

        // Group sampling sites by country
        foreach ($sampling_sites as $site) {
            $country = $site->countries->name ?? 'Unknown country';
            $name = $site->name ?? '';

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

        $labs_available = $this->laboratories_by_country();
        $sampling_sites_available = $this->sampling_sites_by_country();
        $organizations = Organizations::orderBy('name')->get();
        $countries = Countries::orderBy('name')->get();

        $people_available = collect();
        if ($this->projectId) {
            $people_available = People::query()
                ->whereHas('projects', function ($query): void {
                    $query->where('id', $this->projectId);
                })
                ->orderBy('first_name')
                ->get();
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

        return [
            // Create form data (lightweight; no full humans list)
            'sampling_sites_available' => $sampling_sites_available,
            'sample_types_available' => SampleTypes::query()->orderBy('name')->get(),
            'people_available' => $people_available,
            'locations' => Locations::all(),
            'location_lookup_rows' => LookupTableData::locations(),
            'sampling_site_lookup_rows' => LookupTableData::samplingSites(),

            // Nested modals
            'organizations' => $organizations,
            'countries' => $countries,
            'labs_available' => $labs_available,
            'location_types' => $location_types,
            'organization_types' => $organization_types,
            'site_types' => $site_types,
            'ethnicities' => $ethnicities,
            'occupations' => $occupations,
        ];
    }

    public function assign()
    {
        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            $human_samples = HumanSamples::whereHas('tubes', function ($query) {
                $query->where('is_private', 0);
            })->with([
                'humans',
                'sample_types',
                'sampling_sites',
                'sampling_sites.countries',
                'people',
                'locations',
                'projects',
            ])->get();
        } else {
            $human_samples = HumanSamples::with([
                'humans',
                'sample_types',
                'sampling_sites',
                'sampling_sites.countries',
                'people',
                'locations',
                'projects',
            ])->where('projects_id', $this->projectId)->orderBy('created_at', 'desc')
                ->get();

            $human_tubes = Tubes::whereHas('tubes_content', function ($query) {
                $query->where('tubes_content_type', HumanSamples::class);
            })->with(
                'tubes_content',
                'tubes_content.humans',
                'tubes_content.sample_types',
                'tubes_content.sampling_sites',
                'tubes_content.sampling_sites.countries',
                'tubes_content.people',
                'tubes_content.projects'
            )->where('projects_id', $this->projectId)
                ->orderBy('created_at', 'desc')
                ->get();

            $people_available = People::with('projects')
                ->whereHas('projects', function ($query) {
                    $query->where('id', $this->projectId);
                })
                ->get()
                ->sortBy('first_name')
                ->values();

        }

        // Get human samples with unique human ids
        $humans_existing = $human_samples->unique('humans_id')->values();
        $sampling_sites_existing = $human_samples->unique('sampling_sites_id')->values();
        $sample_types_existing = $human_samples->unique('sample_types_id')->values();
        $locations_existing = $human_samples->unique('locations_id')->values();

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

        $unique_purposes = $human_samples->pluck('sample_purpose')->unique()->values();

        $organizations = Organizations::orderBy('name')->get();

        // Get stratified options for select inputs
        $sampling_sites_available = $this->sampling_sites_by_country();
        $sample_types_available = SampleTypes::all()->sortBy('name');
        $labs_available = $this->laboratories_by_country();

        $organization_types = [
            'Government' => 'Government',
            'Private' => 'Private',
            'University' => 'University',
            'Private Company' => 'Private Company',
            'Research Institute' => 'Research Institute',
            'NGO' => 'NGO',
        ];

        $site_types = ['Hospital' => 'Hospital',
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
            'human_samples' => $human_samples,
            'humans' => $this->isGuestMode() ? Humans::all() : Humans::where('projects_id', $this->projectId)->get(),
            'sampling_sites' => SamplingSites::with('countries')->get(),
            'sample_types' => collect(SampleTypes::all())->sortBy('name')->values(),
            'locations' => Locations::all(),
            'location_types' => $location_types,
            'humans_existing' => $humans_existing ?? collect(),
            'sampling_sites_existing' => $sampling_sites_existing ?? collect(),
            'sampling_sites_available' => $sampling_sites_available ?? $this->sampling_sites_by_country(),
            'sample_types_existing' => $sample_types_existing ?? collect(),
            'sample_types_available' => $sample_types_available ?? collect(),
            'labs_available' => $labs_available,
            'unique_purposes' => $unique_purposes ?? collect(),
            'people_available' => $people_available ?? collect(),
            'human_tubes' => $human_tubes ?? collect(),
            'organizations' => $organizations ?? collect(),
            'organization_types' => $organization_types,
            'locations_existing' => $locations_existing ?? collect(),
            'site_types' => $site_types,
            'organization_types' => $organization_types,
            'countries' => Countries::all(),
            'ethnicities' => $ethnicities,
            'occupations' => $occupations,
        ];
    }
}
