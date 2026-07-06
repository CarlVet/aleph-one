<?php

namespace App\Livewire;

use App\Livewire\Concerns\ManagesParasiteSampleObservationPhotos;
use App\Models\AnimalSamples;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\ParasiteSamples;
use App\Models\ParasiteSampleTypes;
use App\Services\SampleExperimentsAggregator;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

class ParasiteSampleProfile extends PlainComponent
{
    use ManagesParasiteSampleObservationPhotos;
    use WithFileUploads;

    protected ?ParasiteSamples $sample = null;

    public $code;

    public $canView = false;

    public $canEdit = true;

    public $unauthorizedMessage = '';

    public ?string $editingField = null;

    public $editingValues = [
        'parasite_sample_types_id' => '',
        'date_processed' => '',
    ];

    public $parasiteSampleTypes = [];

    private function loadSample($code): ParasiteSamples
    {
        $sample = ParasiteSamples::whereHas(
            'parasites', function ($query) {
                $query->whereHasMorph(
                    'parasites_origin',
                    [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class]
                );
            }
        )->with([
            'parasites',
            'parasites.parasite_species',
            'parasites.parasites_origin',
            'parasites.parasites_origin.sampling_sites',
            'parasites.parasites_origin.people',
            'photos',
            'observations' => function ($query): void {
                $query->with($this->observationRelationEagerLoads());
            },
            'parasite_sample_types',
            'people',
            'projects',
            'subProjectAssignment.subProject',
            'experiments' => function ($query) {
                $query->with([
                    'protocols',
                    'protocols.techniques',
                    'pathogens',
                    'people',
                    'laboratories',
                    'experiments_content',
                ]);
            },
            'microplastics' => function ($query) {
                $projectId = $this->selectedProjectId();
                if ($projectId) {
                    $query->where('projects_id', $projectId);
                }
            },
            'microplastics.mps_types',
            'microplastics.protocols',
            'microplastics.laboratories',
            'microplastics.people',
            'nucleic_acids',
            'nucleic_acids.people',
            'nucleic_acids.laboratories',
            'cultures',
            'cultures.people',
            'cultures.laboratories',
            'pools',
            'tubes',
            'tubes.tube_positions',
            'tubes.tube_positions.boxes',
        ])->where('code', $code)->firstOrFail();

        $this->ensureLegacySamplePhotoRecords($sample);

        return $sample;
    }

    public function mount($code): void
    {
        $this->code = $code;
        $this->checkAuthorization();

        if (! $this->canView) {
            return;
        }

        $this->sample = $this->loadSample($code);
        $this->parasiteSampleTypes = ParasiteSampleTypes::query()->orderBy('name')->get();
        $this->syncEditingObservationFields();
    }

    public function hydrate(): void
    {
        if (! $this->canView || $this->code === null) {
            return;
        }

        $this->sample ??= ParasiteSamples::query()->where('code', $this->code)->firstOrFail();
    }

    public function updatedPhoto(): void
    {
        // Selecting a file should not reload the full sample graph.
    }

    private function checkAuthorization(): void
    {
        $selectedProjectId = $this->selectedProjectId();

        if (! $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'No project selected. Please select a project to view this parasite sample profile.';

            return;
        }

        $sample = ParasiteSamples::where('code', $this->code)->first();
        if (! $sample) {
            $this->canView = false;
            $this->unauthorizedMessage = 'Parasite sample not found.';

            return;
        }

        if ((int) $sample->projects_id !== (int) $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'You are not authorized to view the profile of this parasite sample because it does not belong to your selected project.';

            return;
        }

        $this->canEdit = $this->userCanMutateOwnedRecord((int) ($sample->people_id ?? 0), 'parasite_samples');
        $this->canView = true;
    }

    protected function observationSample(): ?ParasiteSamples
    {
        return $this->sample;
    }

    protected function reloadObservationContext(): void
    {
        $this->sample = $this->loadSample($this->code);
    }

    protected function canEditSampleObservationPhotos(): bool
    {
        return $this->canEdit;
    }

    public function startEdit($field): void
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this parasite sample.');

            return;
        }

        if ($field === 'date_processed') {
            $this->editingValues[$field] = $this->sample->date_processed?->format('Y-m-d') ?? '';
        } else {
            $this->editingValues[$field] = (string) ($this->sample->$field ?? '');
        }

        $this->editingField = $field;
    }

    public function saveEdit($field): void
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this parasite sample.');

            return;
        }

        try {
            $rules = [];
            if ($field === 'parasite_sample_types_id') {
                $rules['editingValues.parasite_sample_types_id'] = 'required|integer|exists:parasite_sample_types,id';
            }
            if ($field === 'date_processed') {
                $rules['editingValues.date_processed'] = 'required|date';
            }
            $this->validate($rules);

            if ($field === 'parasite_sample_types_id') {
                $this->sample->update(['parasite_sample_types_id' => (int) $this->editingValues['parasite_sample_types_id']]);
            } elseif ($field === 'date_processed') {
                $this->sample->update(['date_processed' => $this->editingValues['date_processed']]);
            }

            $this->sample = $this->loadSample($this->code);

            $this->editingValues[$field] = '';
            $this->editingField = null;
            $this->dispatch('show-success', message: ucfirst(str_replace('_', ' ', $field)).' updated successfully!');
            session()->flash('message', ucfirst(str_replace('_', ' ', $field)).' updated successfully!');
        } catch (ValidationException $e) {
            $messages = collect($e->errors())->flatten()->toArray();
            $errorMessage = implode("\n", $messages);
            $this->dispatch('show-error', message: $errorMessage);
            session()->flash('error', $errorMessage);
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: 'Failed to update '.str_replace('_', ' ', $field).': '.$e->getMessage());
            session()->flash('error', 'Failed to update '.str_replace('_', ' ', $field).': '.$e->getMessage());
        }
    }

    public function cancelEdit($field): void
    {
        $this->editingValues[$field] = '';
        $this->editingField = null;
    }

    public function deleteParasiteSample()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to delete this parasite sample.');

            return;
        }

        try {
            $this->sample->delete();

            session()->flash('message', 'Parasite sample deleted successfully!');

            return redirect('/samples/parasites/list');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete parasite sample: '.$e->getMessage());
        }
    }

    public function render()
    {
        if (! $this->canView) {
            return view('livewire.parasite-sample-profile', [
                'sample' => null,
                'sampleExperiments' => collect(),
                'canView' => false,
                'canEdit' => false,
                'unauthorizedMessage' => $this->unauthorizedMessage,
                'parasiteSampleTypes' => [],
                'editingField' => null,
            ]);
        }

        $sample = $this->loadSample($this->code);

        return view('livewire.parasite-sample-profile', [
            'sample' => $sample,
            'sampleExperiments' => app(SampleExperimentsAggregator::class)
                ->forSample($sample, $this->selectedProjectId()),
            'canView' => $this->canView,
            'canEdit' => $this->canEdit,
            'canEditPhotoDates' => $this->canEditSampleObservationPhotoDates(),
            'canCommentOnPhotos' => $this->canCommentOnSampleObservationPhotos(),
            'observerPeople' => $this->observerPeopleForProject(),
            'photo' => $this->photo,
            'uploadingPhoto' => $this->uploadingPhoto,
            'uploadError' => $this->uploadError,
            'activePhotoIndex' => $this->activePhotoIndex,
            'showReplyForm' => $this->showReplyForm,
            'unauthorizedMessage' => $this->unauthorizedMessage,
            'parasiteSampleTypes' => $this->parasiteSampleTypes,
            'editingField' => $this->editingField,
        ]);
    }
}
