<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithColumnSorting;
use App\Livewire\Forms\AnimalsForm;
use App\Models\Animals;
use App\Models\Humans;
use App\Models\Organizations;
use App\Services\AnimalsService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\WithPagination;

#[Title('Animals Index')]
class AnimalsIndex extends PlainComponent
{
    use WithColumnSorting;
    use WithFileUploads;
    use WithPagination;

    public AnimalsForm $form;

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
            'animal_code' => 'code',
            'field_id' => 'field_label',
            'species' => fn ($q, $dir) => $this->orderByRelation($q, ['animal_species'], 'name_common', $dir),
            'sex' => 'sex',
            'age' => 'age',
        ];
    }

    public $animalIdFilter;

    public $fieldIdFilter;

    public $speciesFilter;

    public $sexFilter;

    public $ageFilter;

    public $handlerFilter;

    public $locationFilter;

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

    public function updateField($animalId, $field, $value)
    {
        $animal = Animals::find($animalId);
        if (! $animal || ! $this->userCanMutateOwnedRecord((int) $animal->people_id, 'animal_samples')) {
            return;
        }

        $this->form->updateField($animalId, $field, $value);
    }

    public function delete(Animals $animal)
    {
        if (! $this->userCanMutateOwnedRecord((int) $animal->people_id, 'animal_samples')) {
            return;
        }

        $animal->delete();

        $this->form->refreshData();
    }

    public function downloadPhoto(Animals $animal)
    {
        return response()->download(
            Storage::disk('local')->path($animal->pic_path),
            'animal_'.$animal->id.'.png'
        );
    }

    public $isEditing = false; // To track editing state

    // Toggle the editing mode
    public function toggleEditMode()
    {
        if (! $this->userCanWriteModule('animal_samples')) {
            return;
        }

        $this->isEditing = ! $this->isEditing;
    }

    public function updating($field)
    {
        // Reset pagination whenever a filter changes
        $this->resetPage('articles-page');
    }

    protected function applyFilters($query)
    {
        // Apply other filters dynamically if they exist
        if ($this->animalIdFilter) {
            $query->where('code', 'like', '%'.$this->animalIdFilter.'%');
        }
        if ($this->fieldIdFilter) {
            $query->where('field_label', 'like', '%'.$this->fieldIdFilter.'%');
        }
        if ($this->speciesFilter) {
            $query->whereHas('animal_species', function ($q) {
                $q->where('name_common', 'like', '%'.$this->speciesFilter.'%');
            });
        }
        if ($this->sexFilter) {
            $query->where('sex', 'like', '%'.$this->sexFilter.'%');
        }
        if ($this->ageFilter) {
            $query->where('age', 'like', '%'.$this->ageFilter.'%');
        }
        if ($this->handlerFilter) {
            $needle = $this->handlerFilter;

            $query->whereHasMorph('owner', [Humans::class, Organizations::class], function ($q, string $type) use ($needle) {
                if ($type === Humans::class) {
                    $q->where(function ($inner) use ($needle) {
                        $inner->where('title', 'like', '%'.$needle.'%')
                            ->orWhere('first_name', 'like', '%'.$needle.'%')
                            ->orWhere('last_name', 'like', '%'.$needle.'%');
                    });

                    return;
                }

                $q->where('name', 'like', '%'.$needle.'%');
            });
        }
        if ($this->locationFilter) {
            $needle = $this->locationFilter;

            $query->where(function ($q) use ($needle) {
                $q->whereHas('animal_movements.destination_sampling_site', function ($inner) use ($needle) {
                    $inner->where('name', 'like', '%'.$needle.'%');
                })->orWhereHas('animal_movements.source_sampling_site', function ($inner) use ($needle) {
                    $inner->where('name', 'like', '%'.$needle.'%');
                });
            });
        }

        return $query;
    }

    public function export()
    {
        $fileName = 'animals.csv';

        $query = Animals::with([
            'animal_species',
            'owner',
            'latest_movement.source_sampling_site',
            'latest_movement.destination_sampling_site',
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

        $query = $this->applyFilters($query);
        $query = $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $animals = $query->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($animals) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Animal code', 'Field ID', 'Species', 'Sex', 'Age', 'Handler/Owner', 'Location']);

            foreach ($animals as $animal) {
                $owner = $animal->owner;
                $ownerLabel = 'N/A';
                if ($owner instanceof Humans) {
                    $ownerLabel = trim((string) ($owner->title ?? '').' '.(string) ($owner->first_name ?? '').' '.(string) ($owner->last_name ?? '')) ?: 'N/A';
                } elseif ($owner instanceof Organizations) {
                    $ownerLabel = (string) ($owner->name ?? 'N/A');
                }

                $locationLabel = (string) (data_get($animal, 'latest_movement.destination_sampling_site.name')
                    ?: data_get($animal, 'latest_movement.source_sampling_site.name')
                    ?: 'N/A');

                fputcsv($file, [
                    $animal->code,
                    $animal->field_label,
                    trim((string) data_get($animal, 'animal_species.name_common', '').' ('.(string) data_get($animal, 'animal_species.name_scientific', '').')'),
                    $animal->sex,
                    $animal->age,
                    $ownerLabel,
                    $locationLabel,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $service = app(AnimalsService::class);
        $additionalData = $service->assign();

        $query = Animals::with([
            'animal_species',
            'owner',
            'latest_movement.source_sampling_site',
            'latest_movement.destination_sampling_site',
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

        $query = $this->applyFilters($query);
        $query = $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $animals = $query->paginate($this->perPage, pageName: 'articles-page');

        $canEdit = ! $this->isGuestMode() && $this->userCanWriteModule('animal_samples');

        $viewData = array_merge($additionalData, [
            'animals' => $animals,
            'isEditing' => $this->isEditing,
            'isGuestMode' => $this->isGuestMode(),
            'canEdit' => $canEdit,
        ]);

        return view('livewire.animals-index', $viewData);
    }
}
