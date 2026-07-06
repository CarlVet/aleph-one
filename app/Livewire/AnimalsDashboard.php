<?php

namespace App\Livewire;

use App\Models\Animals;
use App\Services\AnimalsService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;

#[Title('Animals Dashboard')]
class AnimalsDashboard extends PlainComponent
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

    public function isGuestMode(): bool
    {
        return $this->projectId === null;
    }

    public function canEdit()
    {
        return Auth::check();
    }

    public function render()
    {
        $service = app(AnimalsService::class);
        $additionalData = $service->assign();

        $query = Animals::with([
            'animal_species',
            'owner',
            'projects',
        ]);

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            // In guest mode, show all animals
            $query = $query;
        } else {
            // In project mode, show animals from the selected project
            $query->where('projects_id', $this->projectId);
        }

        $animals = $query->get();

        // Calculate statistics
        $totalAnimals = $animals->count();
        $speciesCount = $animals->unique('animal_species_id')->count();
        $maleCount = $animals->where('sex', 'Male')->count();
        $femaleCount = $animals->where('sex', 'Female')->count();
        $juvenileCount = $animals->where('age', 'Juvenile')->count();
        $subAdultCount = $animals->where('age', 'Sub-adult')->count();
        $adultCount = $animals->where('age', 'Adult')->count();

        // Species distribution
        $speciesDistribution = $animals->groupBy('animal_species.name_common')
            ->map(function ($group) {
                return $group->count();
            })
            ->sortDesc()
            ->take(10);

        // Age distribution
        $ageDistribution = [
            'Juvenile' => $juvenileCount,
            'Sub-adult' => $subAdultCount,
            'Adult' => $adultCount,
        ];

        // Sex distribution
        $sexDistribution = [
            'Male' => $maleCount,
            'Female' => $femaleCount,
        ];

        $viewData = array_merge($additionalData, [
            'animals' => $animals,
            'totalAnimals' => $totalAnimals,
            'speciesCount' => $speciesCount,
            'maleCount' => $maleCount,
            'femaleCount' => $femaleCount,
            'juvenileCount' => $juvenileCount,
            'subAdultCount' => $subAdultCount,
            'adultCount' => $adultCount,
            'speciesDistribution' => $speciesDistribution,
            'ageDistribution' => $ageDistribution,
            'sexDistribution' => $sexDistribution,
            'isGuestMode' => $this->isGuestMode(),
            'canEdit' => $this->canEdit(),
        ]);

        return view('livewire.animals-dashboard', $viewData);
    }
}
