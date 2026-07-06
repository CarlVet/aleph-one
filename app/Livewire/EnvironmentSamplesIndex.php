<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithColumnSorting;
use App\Livewire\Forms\EnvironmentSamplesForm;
use App\Models\EnvironmentSamples;
use App\Models\Projects;
use App\Models\TubeRequests;
use App\Models\Tubes;
use App\Services\EnvironmentSamplesService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\WithPagination;

#[Title('Environment Samples Index')]
class EnvironmentSamplesIndex extends PlainComponent
{
    use WithColumnSorting;
    use WithFileUploads;
    use WithPagination;

    public EnvironmentSamplesForm $form;

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
            'sub_project' => fn ($q, $dir) => $q->orderBy($this->subProjectCodeSortSubquery($q->getModel()), $dir),
            'sample_type' => fn ($q, $dir) => $this->orderByRelation($q, ['environment_sample_types'], 'name', $dir),
            'date_collected' => 'date_collected',
            'sampling_site' => fn ($q, $dir) => $this->orderByRelation($q, ['sampling_sites'], 'name', $dir),
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'collector' => fn ($q, $dir) => $this->orderByRelation($q, ['people'], 'last_name', $dir),
        ];
    }

    public $sampleIdFilter;

    public $sampleTypeFilter;

    public $startDate;

    public $endDate;

    public $parkFilter;

    public $latitudeFilter;

    public $longitudeFilter;

    public $collectorFilter;

    public $tubeCodeFilter;

    public $subProjectCodeFilter;

    public array $selectedEnvironmentSamples = [];

    public bool $selectAllFiltered = false;

    // Tube request modal properties
    public $showTubeRequestModal = false;

    public $selectedTubeId;

    public $selectedTube;

    public $targetProjectId;

    public $requestMessage = '';

    public $userProjects = [];

    public $sourceProject;

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

    public function updateField($sampleId, $field, $value)
    {
        $sample = EnvironmentSamples::find($sampleId);
        if (! $sample || ! $this->userCanMutateOwnedRecord((int) $sample->people_id, 'environment_samples')) {
            session()->flash('error', 'You do not have permission to edit this record.');

            return;
        }

        $this->form->updateField($sampleId, $field, $value);
    }

    public function delete(EnvironmentSamples $environment_sample)
    {
        if (! $this->userCanMutateOwnedRecord((int) $environment_sample->people_id, 'environment_samples')) {
            session()->flash('error', 'You do not have permission to delete this record.');

            return;
        }

        $environment_sample->delete();

        $this->form->refreshData();
    }

    public function deleteSelected(): void
    {
        $selectedIds = collect($this->selectedEnvironmentSamples)
            ->filter(fn ($checked): bool => (bool) $checked)
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($selectedIds->isEmpty()) {
            session()->flash('error', 'Please select at least one sample.');

            return;
        }

        $samples = EnvironmentSamples::query()
            ->whereIn('id', $selectedIds->all())
            ->get();

        $deleted = 0;
        foreach ($samples as $sample) {
            if (! $this->userCanMutateOwnedRecord((int) $sample->people_id, 'environment_samples')) {
                continue;
            }

            $sample->delete();
            $deleted++;
        }

        $this->selectedEnvironmentSamples = [];
        $this->form->refreshData();

        session()->flash(
            $deleted > 0 ? 'success' : 'error',
            $deleted > 0 ? "{$deleted} selected sample(s) deleted successfully." : 'No selected samples could be deleted.'
        );
    }

    public function removeTube($tubeId)
    {
        if (! $this->userCanWriteModule('tubes')) {
            session()->flash('error', 'You do not have permission to remove tubes in this project.');

            return;
        }

        $tube = Tubes::find($tubeId);

        if ($tube) {
            // Delete the tube entirely
            $tube->delete();

            session()->flash('success', 'Tube deleted successfully!');
        } else {
            session()->flash('error', 'Tube not found!');
        }
    }

    public function openTubeRequestModal($tubeId)
    {
        $this->resetValidation();
        $this->reset(['targetProjectId', 'requestMessage']);

        $this->selectedTubeId = $tubeId;
        $this->selectedTube = Tubes::with(['tubes_content', 'projects'])->find($tubeId);

        if ($this->selectedTube) {
            $this->sourceProject = $this->selectedTube->projects;
        }

        // Load user projects (excluding the source project)
        $user = Auth::user();
        if ($user && $user->people) {
            $this->userProjects = $user->people->projects()
                ->where('projects.id', '!=', $this->sourceProject->id)
                ->get();
        }

        $this->showTubeRequestModal = true;
    }

    public function closeTubeRequestModal()
    {
        $this->showTubeRequestModal = false;
        $this->reset(['selectedTubeId', 'selectedTube', 'targetProjectId', 'requestMessage', 'sourceProject', 'userProjects']);
    }

    public function submitTubeRequest()
    {
        $this->validate([
            'targetProjectId' => 'required|exists:projects,id',
            'requestMessage' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        if (! $user || ! $user->people) {
            session()->flash('error', 'User not found.');

            return;
        }

        if (! $this->selectedTubeId || ! $this->selectedTube) {
            session()->flash('error', 'Tube information is missing.');

            return;
        }

        // Check if there's already a pending request for this tube by this user
        $existingRequest = TubeRequests::where('tubes_id', $this->selectedTubeId)
            ->where('requester_id', $user->people->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            session()->flash('error', 'You already have a pending request for this tube.');

            return;
        }

        try {
            TubeRequests::create([
                'tubes_id' => $this->selectedTubeId,
                'requester_id' => $user->people->id,
                'source_project_id' => $this->sourceProject->id,
                'target_project_id' => $this->targetProjectId,
                'status' => 'pending',
                'request_message' => $this->requestMessage,
            ]);

            session()->flash('success', 'Tube request submitted successfully! The principal investigator will be notified.');
            $this->closeTubeRequestModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to submit request: '.$e->getMessage());
        }
    }

    public function downloadPhoto(EnvironmentSamples $environment_sample)
    {
        return response()->download(
            Storage::disk('local')->path($environment_sample->photo_path),
            'environment_sample_'.$environment_sample->id.'.png'
        );
    }

    public $isEditing = false; // To track editing state

    // Toggle the editing mode
    public function toggleEditMode()
    {
        if (! $this->userCanWriteModule('environment_samples')) {
            session()->flash('error', 'You do not have permission to edit samples in this project.');

            return;
        }

        $this->isEditing = ! $this->isEditing;
    }

    public function updating($field)
    {
        if (
            is_string($field)
            && (
                str_starts_with($field, 'selectedEnvironmentSamples')
                || $field === 'selectAllFiltered'
            )
        ) {
            return;
        }

        // Reset pagination whenever a filter changes
        $this->resetPage('articles-page');
    }

    public function updatedSelectAllFiltered($value): void
    {
        $checked = (bool) $value;

        if (! $checked) {
            $this->selectedEnvironmentSamples = [];

            return;
        }

        $query = EnvironmentSamples::query();

        if ($this->isGuestMode()) {
            $query->whereHas('tubes', function ($q) {
                $q->where('is_private', false);
            });
        } else {
            $query->where(function ($q) {
                $q->where('projects_id', $this->projectId)
                    ->orWhereHas('tubes', function ($tubeQuery) {
                        $tubeQuery->where('projects_id', $this->projectId);
                    });
            });

            $currentPeopleId = (int) ($this->currentPeopleId() ?? 0);
            if ($currentPeopleId <= 0) {
                $this->selectedEnvironmentSamples = [];

                return;
            }

            $query->where('people_id', $currentPeopleId);
        }

        $query = $this->applyFilters($query);

        $ids = $query
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        $this->selectedEnvironmentSamples = $ids
            ->mapWithKeys(fn (int $id): array => [(string) $id => true])
            ->all();
    }

    protected function applyFilters($query)
    {
        // Apply other filters dynamically if they exist
        if ($this->sampleIdFilter) {
            $query->where('code', 'like', '%'.$this->sampleIdFilter.'%');
        }
        if ($this->tubeCodeFilter) {
            $query->whereHas('tubes', function ($q) {
                $q->where(DB::raw("CONCAT(code, ' ', alias_code)"), 'like', '%'.$this->tubeCodeFilter.'%');
            });
        }
        if ($this->sampleTypeFilter) {
            $query->whereHas('environment_sample_types', function ($q) {
                $q->where('name', 'like', '%'.$this->sampleTypeFilter.'%');
            });
        }
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date_collected', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('date_collected', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('date_collected', '<=', $this->endDate);
        }
        if ($this->parkFilter) {
            $query->whereHas('sampling_sites', function ($q) {
                $q->where('name', 'like', '%'.$this->parkFilter.'%');
            });
        }
        if ($this->latitudeFilter) {
            $query->where('latitude', 'like', '%'.$this->latitudeFilter.'%');
        }
        if ($this->longitudeFilter) {
            $query->where('longitude', 'like', '%'.$this->longitudeFilter.'%');
        }
        if ($this->collectorFilter) {
            $query->whereHas('people', function ($q) {
                $q->where('first_name', 'like', '%'.$this->collectorFilter.'%')
                    ->orWhere('last_name', 'like', '%'.$this->collectorFilter.'%');
            });
        }
        if ($this->subProjectCodeFilter) {
            $query->whereHas('subProjectAssignment.subProject', function ($q) {
                $q->where('code', 'like', '%'.$this->subProjectCodeFilter.'%');
            });
        }

        return $query;
    }

    public function export()
    {
        $fileName = 'environment_samples.csv';

        $query = EnvironmentSamples::with([
            'environment_sample_types',
            'sampling_sites',
            'people',
            'projects',
            'tubes' => function ($tubeQuery) {
                if ($this->isGuestMode()) {
                    $tubeQuery->where('is_private', false);
                }
            },
            'tubes.projects',
            'subProjectAssignment.subProject',
        ]);

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            // In guest mode, show only samples that have public tubes
            $query->whereHas('tubes', function ($q) {
                $q->where('is_private', false);
            });
        } else {
            // In project mode, show samples from the selected project
            $query->where(function ($q) {
                $q->where('projects_id', $this->projectId)
                    ->orWhereHas('tubes', function ($tubeQuery) {
                        $tubeQuery->where('projects_id', $this->projectId);
                    });
            });
        }

        $query = $this->applyFilters($query);

        $query = $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $samples = $query->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($samples) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Sample code', 'Sub-project', 'Sample Type', 'Date Collected', 'Sampling site', 'Area', 'Latitude', 'Longitude', 'Collector']);

            foreach ($samples as $sample) {
                fputcsv($file, [
                    $sample->code,
                    data_get($sample, 'subProjectAssignment.subProject.code') ?? 'N/A',
                    $sample->environment_sample_types->name,
                    $sample->date_collected,
                    $sample->sampling_sites->name,
                    $sample->area,
                    $sample->latitude,
                    $sample->longitude,
                    $sample->people->first_name.' '.$sample->people->last_name ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $service = app(EnvironmentSamplesService::class);
        $additionalData = $service->assign();

        $query = EnvironmentSamples::with([
            'environment_sample_types',
            'sampling_sites',
            'people',
            'projects',
            'tubes' => function ($tubeQuery) {
                if ($this->isGuestMode()) {
                    $tubeQuery->where('is_private', false);
                }
            },
            'tubes.projects',
            'subProjectAssignment.subProject',
        ]);

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            // In guest mode, show only samples that have public tubes
            $query->whereHas('tubes', function ($q) {
                $q->where('is_private', false);
            });
        } else {
            // In project mode, show samples from the selected project
            $query->where(function ($q) {
                $q->where('projects_id', $this->projectId)
                    ->orWhereHas('tubes', function ($tubeQuery) {
                        $tubeQuery->where('projects_id', $this->projectId);
                    });
            });
        }

        $query = $this->applyFilters($query);

        $query = $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $environment_samples = $query->paginate($this->perPage, pageName: 'articles-page');

        $canEdit = ! $this->isGuestMode() && $this->userCanWriteModule('environment_samples');

        $viewData = array_merge($additionalData, [
            'environment_samples' => $environment_samples,
            'isEditing' => $this->isEditing,
            'isGuestMode' => $this->isGuestMode(),
            'canEdit' => $canEdit,
        ]);

        return view('livewire.environment-samples-index', $viewData);
    }
}
