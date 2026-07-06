<?php

namespace App\Services;

use App\Models\AnimalHealth;
use App\Models\Animals;
use App\Models\AnimalSpecies;
use App\Models\ClinicalSigns;
use App\Models\Lesions;
use App\Models\People;
use App\Models\Projects;

class AnimalHealthService
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
        $baseCreateData = app(AnimalSamplesService::class)->dataForCreate();

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            $animal_health = AnimalHealth::with([
                'animals',
                'animals.animal_species',
                'clinical_signs',
                'lesions',

            ])->orderBy('created_at', 'desc')->get();

            $people = People::all();
        } else {
            $animal_health = AnimalHealth::with([
                'animals',
                'animals.animal_species',
                'clinical_signs',
                'lesions',

            ])->whereHas('animals', function ($query) {
                $query->where('projects_id', $this->projectId);
            })->orderBy('created_at', 'desc')->get();

            $people = Projects::find($this->projectId)->people;
        }

        // Get existing data for select inputs
        $animals_existing = $animal_health->unique('animals_id')->values();
        $health_statuses_existing = $animal_health->pluck('health_status')->unique()->values();
        $check_types_existing = $animal_health->pluck('check_type')->unique()->values();

        // Get stratified options for select inputs
        $species_by_family = $this->species_by_family();

        // Return data
        return array_merge($baseCreateData, [
            'animal_health' => $animal_health,
            'animals' => $this->isGuestMode() ? Animals::with(['animal_species', 'owner'])->get() : Animals::with(['animal_species', 'owner'])->where('projects_id', $this->projectId)->get(),
            'clinical_signs' => ClinicalSigns::orderBy('name')->get(),
            'lesions' => Lesions::orderBy('name')->get(),
            'animals_existing' => $animals_existing,
            'health_statuses_existing' => $health_statuses_existing,
            'check_types_existing' => $check_types_existing,
            'species_by_family' => $species_by_family,
            'people' => $people,
            'current_project_id' => $this->projectId,
        ]);
    }
}
