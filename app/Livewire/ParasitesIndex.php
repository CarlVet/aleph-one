<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithColumnSorting;
use App\Livewire\Forms\ParasitesForm;
use App\Models\Parasites;
use App\Services\ParasiteSamplesService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\WithPagination;

#[Title('ParasiteSamples Index')]
class ParasitesIndex extends PlainComponent
{
    use WithColumnSorting;
    use WithFileUploads;
    use WithPagination;

    public ParasitesForm $form;

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
            'code' => 'code',
            'species' => fn ($q, $dir) => $this->orderByRelation($q, ['parasite_species'], 'name_scientific', $dir),
            'stage' => 'stage',
            'sex' => 'sex',
            'state' => 'state',
            'date_identified' => 'date_identified',
            'animal_species' => fn ($q, $dir) => $this->orderByRelation($q, ['animal_samples', 'animals', 'animal_species'], 'name_common', $dir),
            'park' => fn ($q, $dir) => $this->orderByRelation($q, ['animal_samples', 'places'], 'name', $dir),
        ];
    }

    public $parasiteIdFilter;

    public $codeFilter;

    public $speciesFilter;

    public $stageFilter;

    public $sexFilter;

    public $stateFilter;

    public $startDate;

    public $endDate;

    public $sampleIdFilter;

    public $animalSpeciesFilter;

    public $parkFilter;

    public function updateField($sampleId, $field, $value)
    {
        $parasite = Parasites::find($sampleId);
        if (! $parasite || ! $this->userCanMutateOwnedRecord((int) $parasite->people_id, 'parasite_samples')) {
            return;
        }

        $this->form->updateField($sampleId, $field, $value);
    }

    public function delete(Parasites $parasite)
    {
        if (! $this->userCanMutateOwnedRecord((int) $parasite->people_id, 'parasite_samples')) {
            return;
        }

        $parasite->delete();

        $this->form->refreshData();
    }

    public function downloadPhoto(Parasites $parasite)
    {
        return response()->download(
            Storage::disk('local')->path($parasite->photo_path),
            'parasite_'.$parasite->id.'.png'
        );
    }

    public $isEditing = false; // To track editing state

    public function toggleEditMode()
    {
        if (! $this->userCanWriteModule('parasite_samples')) {
            return;
        }

        $this->isEditing = ! $this->isEditing;
    }

    public function updating($field)
    {
        $this->resetPage('articles-page');
    }

    public function render()
    {
        $service = app(ParasiteSamplesService::class);

        $additionalData = $service->assign();

        $query = Parasites::with([
            'parasite_species',
            'animal_samples',
            'animal_samples.animals',
            'animal_samples.animals.animal_species',
            'animal_samples.places',
            'people',
            'projects',
        ]);

        // Apply filters dynamically
        if ($this->parasiteIdFilter) {
            $query->where('id', $this->parasiteIdFilter);
        }
        if ($this->codeFilter) {
            $query->where('code', 'like', '%'.$this->codeFilter.'%');
        }
        if ($this->speciesFilter) {
            $query->whereHas('parasite_species', function ($q) {
                $q->where('name_scientific', 'like', '%'.$this->speciesFilter.'%');
            });
        }
        if ($this->stageFilter) {
            $query->where('stage', 'like', '%'.$this->stageFilter.'%');
        }
        if ($this->sexFilter) {
            $query->where('sex', 'like', '%'.$this->sexFilter.'%');
        }
        if ($this->stateFilter) {
            $query->where('state', 'like', '%'.$this->stateFilter.'%');
        }
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date_identified', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('date_identified', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('date_identified', '<=', $this->endDate);
        }
        if ($this->sampleIdFilter) {
            $query->where('animal_samples_id', $this->animalIdFilter);
        }
        if ($this->animalSpeciesFilter) {
            $query->whereHas('animal_samples.animals.animal_species', function ($q) {
                $q->where('name_common', 'like', '%'.$this->animalSpeciesFilter.'%');
            });
        }
        if ($this->parkFilter) {
            $query->whereHas('animal_samples.places', function ($q) {
                $q->where('name', 'like', '%'.$this->parkFilter.'%');
            });
        }

        $this->applySorting($query, $this->sortMap(), ['id', 'desc']);

        $parasites = $query->paginate($this->perPage, pageName: 'articles-page');

        $viewData = array_merge($additionalData, [
            'parasites' => $parasites,
            'isEditing' => $this->isEditing,
            'canEdit' => ! $this->isGuestMode() && $this->userCanWriteModule('parasite_samples'),
        ]);

        return view('livewire.parasites-index', $viewData);
    }
}
