<?php

namespace App\Livewire;

use App\Livewire\Concerns\ExportsTable;
use App\Livewire\Concerns\WithColumnSorting;
use App\Livewire\Forms\AnimalVaccinationForm;
use App\Models\AnimalVaccination;
use App\Services\AnimalSamplesService;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

#[Title('Animal Vaccination Index')]
class AnimalVaccinationIndex extends PlainComponent
{
    use ExportsTable;
    use WithColumnSorting;
    use WithPagination;

    public AnimalVaccinationForm $form;

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
            'vaccine_name' => 'vaccine_name',
            'vaccine_type' => 'vaccine_type',
            'date_administered' => 'date_administered',
            'next_due_date' => 'next_due_date',
            'administered_by' => fn ($q, $dir) => $this->orderByRelation($q, ['people'], 'first_name', $dir),
            'notes' => 'notes',
        ];
    }

    public $animalIdFilter;

    public $speciesFilter;

    public $vaccineNameFilter;

    public $vaccineTypeFilter;

    public $startDate;

    public $endDate;

    public $administeredByFilter;

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

    public function updateField($vaccinationId, $field, $value)
    {
        $vaccination = AnimalVaccination::find($vaccinationId);
        if (! $vaccination || ! $this->userCanMutateOwnedRecord((int) $vaccination->people_id, 'animal_samples')) {
            return;
        }

        $this->form->updateField($vaccinationId, $field, $value);

        $this->dispatch('show-swal',
            icon: 'success',
            title: 'Success!',
            text: 'Field updated successfully!'
        );
    }

    public function delete(AnimalVaccination $animal_vaccination)
    {
        if (! $this->userCanMutateOwnedRecord((int) $animal_vaccination->people_id, 'animal_samples')) {
            return;
        }

        $animal_vaccination->delete();

        $this->form->refreshData();

        $this->dispatch('show-swal',
            icon: 'success',
            title: 'Success!',
            text: 'Animal vaccination record deleted successfully!'
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
        if ($this->vaccineNameFilter) {
            $query->where('vaccine_name', 'like', '%'.$this->vaccineNameFilter.'%');
        }
        if ($this->vaccineTypeFilter) {
            $query->where('vaccine_type', 'like', '%'.$this->vaccineTypeFilter.'%');
        }
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date_administered', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('date_administered', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('date_administered', '<=', $this->endDate);
        }
        if ($this->administeredByFilter) {
            $query->whereHas('people', function ($q) {
                $q->where('first_name', 'like', '%'.$this->administeredByFilter.'%')
                    ->orWhere('last_name', 'like', '%'.$this->administeredByFilter.'%');
            });
        }
        if ($this->notesFilter) {
            $query->where('notes', 'like', '%'.$this->notesFilter.'%');
        }

        return $query;
    }

    public function export(string $format = 'csv')
    {
        $query = AnimalVaccination::with([
            'animals',
            'animals.animal_species',
            'animals.owner',
            'people',
        ]);

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            // In guest mode, show all vaccination records
            $query = $query;
        } else {
            // In project mode, show vaccination records from the selected project
            $query->whereHas('animals', function ($q) {
                $q->where('projects_id', $this->projectId);
            });
        }

        $query = $this->applyFilters($query);
        $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $headers = ['Animal Code', 'Species', 'Vaccine Name', 'Vaccine Type', 'Date Administered', 'Next Due Date', 'Administered By', 'Notes'];

        $rows = $query->get()->map(function ($record) {
            return [
                $record->animals->code ?? 'N/A',
                $record->animals->animal_species->name_common ?? 'N/A',
                $record->vaccine_name ?? 'N/A',
                $record->vaccine_type ?? 'N/A',
                $record->date_administered ?? 'N/A',
                $record->next_due_date ?? 'N/A',
                $record->people->title.' '.$record->people->first_name.' '.$record->people->last_name ?? 'N/A',
                $record->notes ?? 'N/A',
            ];
        });

        return $this->exportTable('animal_vaccinations', $headers, $rows, $format);
    }

    public function render()
    {
        $service = app(AnimalSamplesService::class);
        $additionalData = $service->assign();

        $query = AnimalVaccination::with([
            'animals',
            'animals.animal_species',
            'animals.owner',
            'people',
        ]);

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            // In guest mode, show all vaccination records
            $query = $query;
        } else {
            // In project mode, show vaccination records from the selected project
            $query->whereHas('animals', function ($q) {
                $q->where('projects_id', $this->projectId);
            });
        }

        $query = $this->applyFilters($query);
        $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $animal_vaccinations = $query->paginate($this->perPage, pageName: 'articles-page');

        $canEdit = ! $this->isGuestMode() && $this->userCanWriteModule('animal_samples');

        $viewData = array_merge($additionalData, [
            'animal_vaccinations' => $animal_vaccinations,
            'isEditing' => $this->isEditing,
            'isGuestMode' => $this->isGuestMode(),
            'canEdit' => $canEdit,
        ]);

        return view('livewire.animal-vaccination-index', $viewData);
    }
}
