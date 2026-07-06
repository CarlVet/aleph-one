<?php

namespace App\Services;

use App\Models\Animals;
use App\Models\AnimalSpecies;
use App\Models\AnimalVaccination;
use App\Models\People;
use App\Models\Projects;
use Illuminate\Support\Collection;

class AnimalVaccinationService
{
    protected $projectId;

    private const DEFAULT_VACCINE_OPTIONS = [
        'Bordetella Vaccine',
        'Canine Influenza Vaccine',
        'Canine Parainfluenza Vaccine',
        'Clostridial Vaccine',
        'Distemper Vaccine',
        'Feline Calicivirus Vaccine',
        'Feline Immunodeficiency Virus Vaccine',
        'Feline Leukemia Vaccine',
        'Feline Panleukopenia Vaccine',
        'Feline Rhinotracheitis Vaccine',
        'FVRCP Vaccine',
        'Hepatitis Vaccine',
        'Leptospirosis Vaccine',
        'Lyme Disease Vaccine',
        "Marek's Disease Vaccine",
        'Newcastle Disease Vaccine',
        'Parvovirus Vaccine',
        'Peste des petits ruminants Vaccine',
        'Rabies Booster',
        'Rabies Vaccine',
        'Rift Valley Fever Vaccine',
        'Rotavirus Vaccine',
        'Tetanus Toxoid Vaccine',
        'West Nile Virus Vaccine',
    ];

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
            $animal_vaccinations = AnimalVaccination::with([
                'animals',
                'animals.animal_species',
                'people',
            ])->orderBy('created_at', 'desc')->get();

            $people = People::all();
        } else {
            $animal_vaccinations = AnimalVaccination::with([
                'animals',
                'animals.animal_species',
                'people',
            ])->whereHas('animals', function ($query) {
                $query->where('projects_id', $this->projectId);
            })->orderBy('created_at', 'desc')->get();

            $people = Projects::find($this->projectId)->people;
        }

        // Get existing data for select inputs
        $animals_existing = $animal_vaccinations->unique('animals_id')->values();
        $vaccines_existing = $this->normalizeHistoricOptions(
            $animal_vaccinations->pluck('vaccine_name')->all(),
            self::DEFAULT_VACCINE_OPTIONS
        );
        $vaccine_types_existing = $animal_vaccinations->pluck('vaccine_type')->unique()->values();

        // Get stratified options for select inputs
        $species_by_family = $this->species_by_family();

        // Return data
        return array_merge($baseCreateData, [
            'animal_vaccinations' => $animal_vaccinations,
            'animals' => $this->isGuestMode()
                ? Animals::with(['animal_species', 'owner'])->get()
                : Animals::with(['animal_species', 'owner'])->where('projects_id', $this->projectId)->get(),
            'vaccines_existing' => $vaccines_existing,
            'vaccine_types_existing' => $vaccine_types_existing,
            'species_by_family' => $species_by_family,
            'people' => $people,
            'current_project_id' => $this->projectId,
        ]);
    }

    /**
     * @param  array<int, mixed>  $rawValues
     * @param  array<int, string>  $defaults
     * @return Collection<int, string>
     */
    private function normalizeHistoricOptions(array $rawValues, array $defaults)
    {
        return collect($rawValues)
            ->prepend(...array_reverse($defaults))
            ->flatMap(function ($value) {
                return preg_split('/\s*[,;\n|]+\s*/', (string) $value) ?: [];
            })
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique(fn ($value) => mb_strtolower($value))
            ->sort(fn ($a, $b) => strcasecmp($a, $b))
            ->values();
    }
}
