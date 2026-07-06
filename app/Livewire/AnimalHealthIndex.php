<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithColumnSorting;
use App\Livewire\Forms\AnimalHealthForm;
use App\Models\AnimalHealth;
use App\Services\AnimalHealthService;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

#[Title('Animal Health Index')]
class AnimalHealthIndex extends PlainComponent
{
    use WithColumnSorting;
    use WithPagination;

    public AnimalHealthForm $form;

    protected function sortingPageName(): ?string
    {
        return 'articles-page';
    }

    /**
     * @return array<string, string|callable>
     */
    protected function sortMap(): array
    {
        return [
            'animal_code' => fn ($q, $dir) => $this->orderByRelation($q, ['animals'], 'code', $dir),
            'species' => fn ($q, $dir) => $this->orderByRelation($q, ['animals', 'animal_species'], 'name_common', $dir),
            'health_status' => 'health_status',
            'check_date' => 'check_date',
            'check_type' => 'check_type',
            'alive' => 'alive',
            'notes' => 'notes',
        ];
    }

    public $animalIdFilter;

    public $speciesFilter;

    public $healthStatusFilter;

    public $checkTypeFilter;

    public $startDate;

    public $endDate;

    public $aliveFilter;

    public $clinicalSignsFilter;

    public $lesionsFilter;

    public $notesFilter;

    public $isEditing = false;

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

    public function updateField($healthId, $field, $value)
    {
        $health = AnimalHealth::find($healthId);
        if (! $health || ! $this->userCanMutateOwnedRecord((int) $health->people_id, 'animal_samples')) {
            return;
        }

        $this->form->updateField($healthId, $field, $value);

        $this->dispatch('show-swal',
            icon: 'success',
            title: 'Success!',
            text: 'Field updated successfully!'
        );
    }

    public function delete(AnimalHealth $animal_health)
    {
        if (! $this->userCanMutateOwnedRecord((int) $animal_health->people_id, 'animal_samples')) {
            return;
        }

        $animal_health->delete();

        $this->form->refreshData();

        $this->dispatch('show-swal',
            icon: 'success',
            title: 'Success!',
            text: 'Animal health record deleted successfully!'
        );
    }

    public function toggleEditMode()
    {
        if (! $this->userCanWriteModule('animal_samples')) {
            return;
        }

        $this->isEditing = ! $this->isEditing;
    }

    public function updating($field)
    {
        $this->resetPage('articles-page');
    }

    protected function applyFilters($query)
    {
        if ($this->animalIdFilter) {
            $query->whereHas('animals', function ($q) {
                $q->where('code', 'like', '%'.$this->animalIdFilter.'%');
            });
        }
        if ($this->speciesFilter) {
            $query->whereHas('animals.animal_species', function ($q) {
                $q->where('name_common', 'like', '%'.$this->speciesFilter.'%');
            });
        }
        if ($this->healthStatusFilter) {
            $query->where('health_status', 'like', '%'.$this->healthStatusFilter.'%');
        }
        if ($this->checkTypeFilter) {
            $query->where('check_type', 'like', '%'.$this->checkTypeFilter.'%');
        }
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('check_date', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('check_date', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('check_date', '<=', $this->endDate);
        }
        if ($this->aliveFilter !== null && $this->aliveFilter !== '') {
            $query->where('alive', (int) $this->aliveFilter);
        }
        if ($this->clinicalSignsFilter) {
            $query->whereHas('clinical_signs', function ($q) {
                $q->where('name', 'like', '%'.$this->clinicalSignsFilter.'%');
            });
        }
        if ($this->lesionsFilter) {
            $query->whereHas('lesions', function ($q) {
                $q->where('name', 'like', '%'.$this->lesionsFilter.'%');
            });
        }
        if ($this->notesFilter) {
            $query->where('notes', 'like', '%'.$this->notesFilter.'%');
        }

        return $query;
    }

    public function export()
    {
        $fileName = 'animal_health.csv';

        $query = AnimalHealth::with([
            'animals',
            'animals.animal_species',
            'animals.owner',
            // removed 'animals.humans', 'animals.places'
            'clinical_signs',
            'lesions',
        ]);

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            // In guest mode, show all health records
            // No additional filtering needed
        } else {
            // In project mode, show health records from the selected project
            $query->whereHas('animals', function ($q) {
                $q->where('projects_id', $this->projectId);
            });
        }

        $query = $this->applyFilters($query);
        $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $healthRecords = $query->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($healthRecords) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Animal Code', 'Species', 'Health Status', 'Check Date', 'Check Type', 'Clinical Signs', 'Lesions', 'Alive', 'Notes']);

            foreach ($healthRecords as $record) {
                // Get multiple clinical signs
                $clinicalSigns = $record->clinical_signs_many->pluck('name')->implode(', ');
                if (empty($clinicalSigns) && $record->clinical_signs) {
                    $clinicalSigns = $record->clinical_signs->name;
                }

                // Get multiple lesions
                $lesions = $record->lesions_many->pluck('name')->implode(', ');
                if (empty($lesions) && $record->lesions) {
                    $lesions = $record->lesions->name;
                }

                fputcsv($file, [
                    $record->animals->code ?? 'N/A',
                    $record->animals->animal_species->name_common ?? 'N/A',
                    $record->health_status ?? 'N/A',
                    $record->check_date ?? 'N/A',
                    $record->check_type ?? 'N/A',
                    $clinicalSigns ?: 'N/A',
                    $lesions ?: 'N/A',
                    $record->alive ? 'Yes' : 'No',
                    $record->notes ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $service = app(AnimalHealthService::class);
        $additionalData = $service->assign();

        $query = AnimalHealth::with([
            'animals',
            'animals.animal_species',
            'animals.owner',
            // removed 'animals.humans', 'animals.places'
            'clinical_signs',
            'lesions',
        ]);

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            // In guest mode, show all health records
            // No additional filtering needed
        } else {
            // In project mode, show health records from the selected project
            $query->whereHas('animals', function ($q) {
                $q->where('projects_id', $this->projectId);
            });
        }

        $query = $this->applyFilters($query);
        $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $animal_health = $query->paginate($this->perPage, pageName: 'articles-page');

        $canEdit = ! $this->isGuestMode() && $this->userCanWriteModule('animal_samples');

        $viewData = array_merge($additionalData, [
            'animal_health' => $animal_health,
            'isEditing' => $this->isEditing,
            'isGuestMode' => $this->isGuestMode(),
            'canEdit' => $canEdit,
        ]);

        return view('livewire.animal-health-index', $viewData);
    }
}
