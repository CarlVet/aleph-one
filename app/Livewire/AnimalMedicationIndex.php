<?php

namespace App\Livewire;

use App\Livewire\Concerns\ExportsTable;
use App\Livewire\Concerns\WithColumnSorting;
use App\Livewire\Forms\AnimalMedicationForm;
use App\Models\AnimalMedication;
use App\Services\AnimalSamplesService;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

#[Title('Animal Medication Index')]
class AnimalMedicationIndex extends PlainComponent
{
    use ExportsTable;
    use WithColumnSorting;
    use WithPagination;

    public AnimalMedicationForm $form;

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
            'medication_name' => 'medication_name',
            'dosage' => 'dosage',
            'start_date' => 'start_date',
            'end_date' => 'end_date',
            'prescribed_by' => fn ($q, $dir) => $this->orderByRelation($q, ['people'], 'first_name', $dir),
            'notes' => 'notes',
        ];
    }

    public $animalIdFilter;

    public $speciesFilter;

    public $medicationNameFilter;

    public $dosageFilter;

    public $startDate;

    public $endDate;

    public $prescribedByFilter;

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

    public function updateField($medicationId, $field, $value)
    {
        $medication = AnimalMedication::find($medicationId);
        if (! $medication || ! $this->userCanMutateOwnedRecord((int) $medication->people_id, 'animal_samples')) {
            return;
        }

        $this->form->updateField($medicationId, $field, $value);

        $this->dispatch('show-swal',
            icon: 'success',
            title: 'Success!',
            text: 'Field updated successfully!'
        );
    }

    public function delete(AnimalMedication $animal_medication)
    {
        if (! $this->userCanMutateOwnedRecord((int) $animal_medication->people_id, 'animal_samples')) {
            return;
        }

        $animal_medication->delete();

        $this->form->refreshData();

        $this->dispatch('show-swal',
            icon: 'success',
            title: 'Success!',
            text: 'Animal medication record deleted successfully!'
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
        if ($this->medicationNameFilter) {
            $query->where('medication_name', 'like', '%'.$this->medicationNameFilter.'%');
        }
        if ($this->dosageFilter) {
            $query->where('dosage', 'like', '%'.$this->dosageFilter.'%');
        }
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('start_date', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('start_date', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('start_date', '<=', $this->endDate);
        }
        if ($this->prescribedByFilter) {
            $query->whereHas('people', function ($q) {
                $q->where('first_name', 'like', '%'.$this->prescribedByFilter.'%')
                    ->orWhere('last_name', 'like', '%'.$this->prescribedByFilter.'%');
            });
        }
        if ($this->notesFilter) {
            $query->where('notes', 'like', '%'.$this->notesFilter.'%');
        }

        return $query;
    }

    public function export(string $format = 'csv')
    {
        $query = AnimalMedication::with([
            'animals',
            'animals.animal_species',
            'animals.owner',
            'people',
        ]);

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            // In guest mode, show all medication records
            $query = $query;
        } else {
            // In project mode, show medication records from the selected project
            $query->whereHas('animals', function ($q) {
                $q->where('projects_id', $this->projectId);
            });
        }

        $query = $this->applyFilters($query);
        $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $headers = ['Animal Code', 'Species', 'Medication Name', 'Dosage', 'Start Date', 'End Date', 'Prescribed By', 'Notes'];

        $rows = $query->get()->map(function ($record) {
            return [
                $record->animals->code ?? 'N/A',
                $record->animals->animal_species->name_common ?? 'N/A',
                $record->medication_name ?? 'N/A',
                $record->dosage ?? 'N/A',
                $record->start_date ?? 'N/A',
                $record->end_date ?? 'N/A',
                $record->people->title.' '.$record->people->first_name.' '.$record->people->last_name ?? 'N/A',
                $record->notes ?? 'N/A',
            ];
        });

        return $this->exportTable('animal_medications', $headers, $rows, $format);
    }

    public function render()
    {
        $service = app(AnimalSamplesService::class);
        $additionalData = $service->assign();

        $query = AnimalMedication::with([
            'animals',
            'animals.animal_species',
            'animals.owner',
            'people',
        ]);

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            // In guest mode, show all medication records
            $query = $query;
        } else {
            // In project mode, show medication records from the selected project
            $query->whereHas('animals', function ($q) {
                $q->where('projects_id', $this->projectId);
            });
        }

        $query = $this->applyFilters($query);
        $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $animal_medications = $query->paginate($this->perPage, pageName: 'articles-page');

        $canEdit = ! $this->isGuestMode() && $this->userCanWriteModule('animal_samples');

        $viewData = array_merge($additionalData, [
            'animal_medications' => $animal_medications,
            'isEditing' => $this->isEditing,
            'isGuestMode' => $this->isGuestMode(),
            'canEdit' => $canEdit,
        ]);

        return view('livewire.animal-medication-index', $viewData);
    }
}
