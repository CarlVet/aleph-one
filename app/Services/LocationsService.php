<?php

namespace App\Services;

use App\Models\Laboratories;
use App\Models\Locations;

class LocationsService
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

    public function assign()
    {
        return [
            'location_types' => [
                'Stand-up freezer' => 'Stand-up freezer',
                'Chest freezer' => 'Chest freezer',
                'Refrigerator' => 'Refrigerator',
                'Walk-in refrigerator' => 'Walk-in refrigerator',
                'Walk-in freezer' => 'Walk-in freezer',
                'Shelf' => 'Shelf',
                'Cabinet' => 'Cabinet',
                'Drawer' => 'Drawer',
            ],
            'labs_available' => $this->laboratories_by_country(),
            'locations' => Locations::with('laboratories')->orderBy('name')->get(),
        ];
    }

    public function laboratories_by_country()
    {
        $laboratories = Laboratories::all();

        $labs_by_country = [];

        foreach ($laboratories as $lab) {
            $country = $lab['country'] ?? 'Unknown country';
            $name = $lab['name'] ?? '';

            if ($name) {
                $labs_by_country[$country][] = [
                    'name' => $name,
                    'type' => 'laboratory',
                ];
            }
        }

        ksort($labs_by_country);

        foreach ($labs_by_country as $country => $labs_list) {
            usort($labs_by_country[$country], function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
        }

        return $labs_by_country;
    }
}
