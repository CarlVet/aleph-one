<?php

namespace App\Services;

use App\Models\Animals;
use App\Models\AnimalSpecies;
use App\Models\Humans;
use App\Models\Organizations;

class AnimalsService
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
            $name_scientific = $species['name_scientific'] ?? '';

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
            $animals = Animals::with([
                'animal_species',
                'owner',
                'latest_movement.source_sampling_site',
                'latest_movement.destination_sampling_site',
                'projects',
            ])->get();

            $humans = Humans::all();
        } else {
            $animals = Animals::with([
                'animal_species',
                'owner',
                'latest_movement.source_sampling_site',
                'latest_movement.destination_sampling_site',
                'projects',
            ])->where('projects_id', $this->projectId)
                ->get();

            $humans = Humans::where('projects_id', $this->projectId)->get();
        }

        // Get stratified options for select inputs
        $species_by_family = $this->species_by_family();

        // Return data
        return [
            'animals' => $animals,
            'animal_species' => AnimalSpecies::all(),
            'organizations' => Organizations::all(),
            'species_by_family' => $species_by_family,
            'humans' => $humans,
            'current_project_id' => $this->projectId,
        ];
    }
}
