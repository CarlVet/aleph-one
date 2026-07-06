<?php

namespace App\Services;

use App\Models\Boxes;
use App\Models\Countries;
use App\Models\Laboratories;
use App\Models\Locations;
use App\Models\Organizations;
use App\Models\People;

class BoxesService
{
    protected $projectId;

    public function __construct()
    {
        $this->projectId = session('selected_project_id'); // Default to project 1 if session is empty
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
        // Check if a project is selected
        if (! $this->projectId) {
            return [
                'boxes' => collect([]), // Empty collection
                'animal_boxes' => collect([]),
                'parasite_boxes' => collect([]),
                'nucleic_boxes' => collect([]),
                'locations' => collect([]),
                'labs' => collect([]),
                'people' => collect(),
            ];
        }

        $boxes = Boxes::where('projects_id', $this->projectId)->get();

        $animal_boxes = Boxes::where('content_type', 'Animal samples')->get();
        $parasite_boxes = Boxes::where('content_type', 'Parasite samples')->get();
        $nucleic_boxes = Boxes::where('content_type', 'Nucleic acids')->get();

        $locations = Locations::with('laboratories')->get();

        $labs = Laboratories::all();

        $people = People::whereHas('projects', function ($query) {
            $query->where('projects.id', $this->projectId);
        })->orderBy('last_name')->orderBy('first_name')->get();

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

        $labs_available = $this->laboratories_by_country();

        // Return data
        return [
            'boxes' => $boxes,
            'animal_boxes' => $animal_boxes,
            'parasite_boxes' => $parasite_boxes,
            'nucleic_boxes' => $nucleic_boxes,
            'locations' => $locations,
            'labs' => $labs,
            'people' => $people,
            'location_types' => $location_types,
            'organization_types' => $organization_types,
            'site_types' => $site_types,
            'labs_available' => $labs_available,
            'organizations' => Organizations::all(),
            'countries' => Countries::all(),
            'current_project_id' => $this->projectId,
        ];
    }
}
