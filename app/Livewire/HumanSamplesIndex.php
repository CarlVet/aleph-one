<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithColumnSorting;
use App\Models\HumanSamples;
use App\Models\Projects;
use App\Models\SampleTypes;
use App\Models\SamplingSites;
use App\Models\TubeRequests;
use App\Models\Tubes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class HumanSamplesIndex extends PlainComponent
{
    use WithColumnSorting;
    use WithFileUploads;
    use WithPagination;

    protected $projectId;

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
            'patient' => fn ($q, $dir) => $this->orderByRelation($q, ['humans'], 'code', $dir),
            'sample_type' => fn ($q, $dir) => $this->orderByRelation($q, ['sample_types'], 'name', $dir),
            'collection_date' => 'date_collected',
            'collector' => fn ($q, $dir) => $this->orderByRelation($q, ['people'], 'last_name', $dir),
            'sampling_site' => fn ($q, $dir) => $this->orderByRelation($q, ['sampling_sites'], 'name', $dir),
            'purpose' => 'sample_purpose',
            'storage_state' => 'storage_state',
            'processed' => 'processed',
        ];
    }

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

    public $photo;

    public $uploadingPhotoId = null;

    public $uploadErrors = [];

    public $currentPhotoId = null;

    public $sampleIdFilter;

    public $tubeCodeFilter;

    public $patientFilter;

    public $sampleTypeFilter;

    public $collectionDateStart;

    public $collectionDateEnd;

    public $collectorFilter;

    public $samplingSiteFilter;

    public $purposeFilter;

    public $storageStateFilter;

    public $processedFilter;

    public $subProjectCodeFilter;

    public array $selectedHumanSamples = [];

    public bool $selectAllFiltered = false;

    public function updateField($sampleId, $field, $value)
    {
        $sample = HumanSamples::find($sampleId);
        if (! $sample || ! $this->userCanMutateOwnedRecord((int) $sample->people_id, 'human_samples')) {
            session()->flash('error', 'You do not have permission to edit this record.');

            return;
        }

        if ($sample) {
            switch ($field) {
                case 'sample_type':
                    $type = SampleTypes::where('name', $value)->first();
                    $sample->update(['sample_types_id' => $type->id]);
                    break;
                case 'sample_purpose':
                    $sample->update(['sample_purpose' => $value]);
                    break;
                case 'storage_state':
                    $sample->update(['storage_state' => $value]);
                    break;
                case 'processed':
                    $sample->update(['processed' => $value === 'true']);
                    break;
                case 'date_collected':
                    $sample->update(['date_collected' => $value]);
                    break;
                case 'sampling_site':
                    $sampling_site = SamplingSites::where('name', $value)->first();
                    $sample->update(['sampling_sites_id' => $sampling_site->id]);
                    break;
            }

            session()->flash('success', 'Sample updated successfully!');
        }
    }

    public function delete(HumanSamples $sample)
    {
        if (! $this->userCanMutateOwnedRecord((int) $sample->people_id, 'human_samples')) {
            session()->flash('error', 'You do not have permission to delete this record.');

            return;
        }

        $sample->delete();
        session()->flash('success', 'Sample deleted successfully!');
    }

    public function deleteSelected(): void
    {
        $selectedIds = collect($this->selectedHumanSamples)
            ->filter(fn ($checked): bool => (bool) $checked)
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($selectedIds->isEmpty()) {
            session()->flash('error', 'Please select at least one sample.');

            return;
        }

        $samples = HumanSamples::query()
            ->whereIn('id', $selectedIds->all())
            ->get();

        $deleted = 0;
        foreach ($samples as $sample) {
            if (! $this->userCanMutateOwnedRecord((int) $sample->people_id, 'human_samples')) {
                continue;
            }

            $sample->delete();
            $deleted++;
        }

        $this->selectedHumanSamples = [];

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

    public $isEditing = false;

    // Tube request modal properties
    public $showTubeRequestModal = false;

    public $selectedTubeId;

    public $selectedTube;

    public $targetProjectId;

    public $requestMessage = '';

    public $userProjects = [];

    public $sourceProject;

    public function toggleEditMode()
    {
        if (! $this->userCanWriteModule('human_samples')) {
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
                str_starts_with($field, 'selectedHumanSamples')
                || $field === 'selectAllFiltered'
            )
        ) {
            return;
        }

        $this->resetPage('articles-page');
    }

    public function updatedSelectAllFiltered($value): void
    {
        $checked = (bool) $value;

        if (! $checked) {
            $this->selectedHumanSamples = [];

            return;
        }

        $query = HumanSamples::query();

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
                $this->selectedHumanSamples = [];

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

        $this->selectedHumanSamples = $ids
            ->mapWithKeys(fn (int $id): array => [(string) $id => true])
            ->all();
    }

    protected function applyFilters($query)
    {
        if ($this->sampleIdFilter) {
            $query->where('code', 'like', '%'.$this->sampleIdFilter.'%');
        }
        if ($this->tubeCodeFilter) {
            $query->whereHas('tubes', function ($q) {
                $q->where(DB::raw("CONCAT(code, ' ', alias_code)"), 'like', '%'.$this->tubeCodeFilter.'%');
            });
        }
        if ($this->patientFilter) {
            $query->whereHas('humans', function ($q) {
                $q->where('code', 'like', '%'.$this->patientFilter.'%');
            });
        }
        if ($this->sampleTypeFilter) {
            $query->whereHas('sample_types', function ($q) {
                $q->where('name', 'like', '%'.$this->sampleTypeFilter.'%');
            });
        }
        if ($this->collectionDateStart && $this->collectionDateEnd) {
            $query->whereBetween('date_collected', [$this->collectionDateStart, $this->collectionDateEnd]);
        } elseif ($this->collectionDateStart) {
            $query->where('date_collected', '>=', $this->collectionDateStart);
        } elseif ($this->collectionDateEnd) {
            $query->where('date_collected', '<=', $this->collectionDateEnd);
        }
        if ($this->collectorFilter) {
            $query->whereHas('people', function ($q) {
                $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$this->collectorFilter.'%');
            });
        }
        if ($this->samplingSiteFilter) {
            $query->whereHas('sampling_sites', function ($q) {
                $q->where('name', 'like', '%'.$this->samplingSiteFilter.'%');
            });
        }
        if ($this->purposeFilter) {
            $query->where('sample_purpose', 'like', '%'.$this->purposeFilter.'%');
        }
        if ($this->storageStateFilter) {
            $query->where('storage_state', 'like', '%'.$this->storageStateFilter.'%');
        }
        if ($this->processedFilter !== null) {
            $query->where('processed', $this->processedFilter === 'true');
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
        $fileName = 'human_samples.csv';

        $query = HumanSamples::with([
            'humans',
            'sample_types',
            'people',
            'sampling_sites',
            'locations',
            'tubes',
            'projects',
            'tubes' => function ($tubeQuery) {
                if ($this->isGuestMode()) {
                    $tubeQuery->where('is_private', false);
                }
            },
            'tubes.projects',
            'subProjectAssignment.subProject',
        ])->orderBy('created_at', 'desc');

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
            fputcsv($file, [
                'Sample Code',
                'Sub-project',
                'Patient Name',
                'Sample Type',
                'Collection Date',
                'Collector',
                'Sampling Site',
                'Area',
                'Coordinates',
                'Purpose',
                'Storage State',
                'Processed',
            ]);

            foreach ($samples as $sample) {
                fputcsv($file, [
                    $sample->code,
                    data_get($sample, 'subProjectAssignment.subProject.code') ?? 'N/A',
                    $sample->humans->first_name.' '.$sample->humans->last_name,
                    $sample->sample_types->name,
                    $sample->date_collected,
                    $sample->people->first_name.' '.$sample->people->last_name,
                    $sample->sampling_sites->name,
                    $sample->area,
                    $sample->latitude && $sample->longitude ? "{$sample->latitude}, {$sample->longitude}" : 'N/A',
                    $sample->sample_purpose,
                    $sample->storage_state,
                    $sample->processed ? 'Yes' : 'No',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function uploadPhoto($sampleId)
    {
        try {
            Log::info('Starting photo upload for sample ID: '.$sampleId);

            if (! $this->photo) {
                Log::error('No photo was selected for sample ID: '.$sampleId);
                $this->uploadErrors[$sampleId] = 'No photo was selected';

                return;
            }

            $maxSize = 50 * 1024 * 1024; // 50MB in bytes
            if ($this->photo->getSize() > $maxSize) {
                Log::error('File too large for sample ID: '.$sampleId);
                $this->uploadErrors[$sampleId] = 'File size exceeds 50MB limit';

                return;
            }

            $this->validate([
                'photo' => 'required|file|mimes:jpg,jpeg,png,webp,tif,tiff,pdf|max:51200',
            ], [
                'photo.max' => 'The photo size must not exceed 50MB.',
                'photo.mimes' => 'Unsupported format. Allowed formats: JPG, PNG, WEBP, TIFF, PDF.',
            ]);

            $sample = HumanSamples::find($sampleId);

            if (! $sample) {
                Log::error('Sample not found with ID: '.$sampleId);
                $this->uploadErrors[$sampleId] = 'Sample not found';

                return;
            }

            $this->uploadingPhotoId = $sampleId;

            if ($sample->photo_path) {
                Storage::disk('local')->delete($sample->photo_path);
            }

            $fileName = 'human_sample_'.$sampleId.'_'.time().'.'.$this->photo->getClientOriginalExtension();

            try {
                $path = $this->photo->storeAs('human-sample-photos', $fileName, 'local');
            } catch (\Exception $e) {
                Log::error('Storage error for sample ID: '.$sampleId.': '.$e->getMessage());
                $this->uploadErrors[$sampleId] = 'Failed to store photo: '.$e->getMessage();

                return;
            }

            if (! $path) {
                Log::error('Failed to store photo for sample ID: '.$sampleId);
                $this->uploadErrors[$sampleId] = 'Failed to store photo';

                return;
            }

            $sample->update([
                'photo_path' => $path,
            ]);

            if (! Storage::disk('local')->exists($path)) {
                Log::error('File not found after upload at path: '.$path);
                $this->uploadErrors[$sampleId] = 'File was not stored properly';

                return;
            }

            $this->reset(['photo', 'uploadingPhotoId', 'currentPhotoId']);
            unset($this->uploadErrors[$sampleId]);

            session()->flash('message', 'Photo uploaded successfully');

        } catch (\Exception $e) {
            Log::error('Photo upload failed for sample ID '.$sampleId.': '.$e->getMessage());
            $this->uploadErrors[$sampleId] = 'Failed to upload photo: '.$e->getMessage();
            $this->reset(['photo', 'uploadingPhotoId', 'currentPhotoId']);
        }
    }

    public function render()
    {
        $query = HumanSamples::with([
            'humans',
            'sample_types',
            'people',
            'sampling_sites',
            'locations',
            'tubes',
            'projects',
            'tubes' => function ($tubeQuery) {
                if ($this->isGuestMode()) {
                    $tubeQuery->where('is_private', false);
                }
            },
            'tubes.projects',
            'subProjectAssignment.subProject',
        ])->orderBy('created_at', 'desc');

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

        $samples = $query->paginate($this->perPage, pageName: 'articles-page');

        $sampleTypes = SampleTypes::all();
        $sampling_sites = SamplingSites::all();

        $canEdit = ! $this->isGuestMode() && $this->userCanWriteModule('human_samples');

        return view('livewire.human-samples-index', [
            'samples' => $samples,
            'isEditing' => $this->isEditing,
            'sampleTypes' => $sampleTypes,
            'sampling_sites' => $sampling_sites,
            'isGuestMode' => $this->isGuestMode(),
            'canEdit' => $canEdit,
        ]);
    }
}
