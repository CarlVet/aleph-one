<?php

namespace App\Livewire;

use App\Models\Experiments;
use App\Models\NucleicAcids;
use App\Models\Tubes;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

class ExperimentProfile extends PlainComponent
{
    use WithFileUploads;

    public $experiment;

    public $code;

    public $photo;

    public $uploadingPhoto = false;

    public $uploadError = null;

    public $canView = false;

    public $canEdit = true;

    public $unauthorizedMessage = '';

    // Inline editing properties
    public $editingValues = [
        'outcome_discrete' => '',
        'outcome_quant' => '',
        'date_tested' => '',
    ];

    public function mount($code)
    {
        $this->code = $code;
        $this->checkAuthorization();

        if (! $this->canView) {
            return;
        }

        $this->loadExperiment();
    }

    private function checkAuthorization(): void
    {
        $selectedProjectId = $this->selectedProjectId();

        $experiment = Experiments::where('code', $this->code)->first();
        if (! $experiment) {
            $this->canView = false;
            $this->unauthorizedMessage = 'Experiment not found.';

            return;
        }

        if (! $selectedProjectId) {
            if ($experiment->is_private === false) {
                $this->canView = true;
                $this->canEdit = false;

                return;
            }

            $this->canView = false;
            $this->unauthorizedMessage = 'This experiment is private.';

            return;
        }

        if ((int) $experiment->projects_id !== (int) $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'You are not authorized to view the profile of this experiment because it does not belong to your selected project.';

            return;
        }

        $this->canEdit = $this->userCanMutateOwnedRecord((int) ($experiment->people_id ?? 0), 'experiments');
        $this->canView = true;
    }

    private function loadExperiment(): void
    {
        $this->experiment = Experiments::with([
            'protocols',
            'protocols.techniques',
            'experiments_content',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'subProjectAssignment.subProject',
        ])->where('code', $this->code)->firstOrFail();
    }

    private function loadExperimentWithRelationships(): Experiments
    {
        return Experiments::with([
            'protocols',
            'protocols.techniques',
            'experiments_content',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'subProjectAssignment.subProject',
        ])->where('code', $this->code)->firstOrFail();
    }

    // Inline editing methods (click-to-edit)
    public function startEdit($field): void
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this experiment.');

            return;
        }

        $this->experiment = $this->loadExperimentWithRelationships();

        if ($field === 'date_tested') {
            $this->editingValues[$field] = $this->experiment->date_tested?->format('Y-m-d') ?? '';
        } else {
            $this->editingValues[$field] = (string) ($this->experiment->$field ?? '');
        }

        $this->dispatch('start-edit', field: $field);
    }

    public function saveEdit($field): void
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this experiment.');

            return;
        }

        try {
            $rules = [];
            if ($field === 'outcome_discrete') {
                $rules['editingValues.outcome_discrete'] = 'nullable|string|max:255';
            }
            if ($field === 'outcome_quant') {
                $rules['editingValues.outcome_quant'] = 'nullable|numeric|min:0';
            }
            if ($field === 'date_tested') {
                $rules['editingValues.date_tested'] = 'required|date';
            }

            $this->validate($rules);

            $value = $this->editingValues[$field];
            if ($value === '') {
                $value = null;
            }

            $this->experiment->update([$field => $value]);

            $this->experiment = $this->loadExperimentWithRelationships();

            $this->editingValues[$field] = '';
            $this->dispatch('save-edit', field: $field);
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
        $this->experiment = $this->loadExperimentWithRelationships();
        $this->editingValues[$field] = '';
        $this->dispatch('cancel-edit', field: $field);
    }

    public function uploadPhoto()
    {
        if (! $this->canEdit) {
            $this->uploadError = 'You do not have permission to upload a photo for this experiment.';

            return;
        }

        if (! $this->photo) {
            $this->uploadError = 'Please select a file first.';

            return;
        }

        // Check file size (50MB = 52428800 bytes)
        if ($this->photo->getSize() > 52428800) {
            $this->uploadError = 'The selected file is too large. Maximum allowed size is 50MB.';
            $this->photo = null;

            return;
        }

        $this->validate([
            'photo' => 'required|file|mimes:jpg,jpeg,png,webp,tif,tiff,pdf|max:51200', // 50MB max
        ], [
            'photo.mimes' => 'Unsupported format. Allowed formats: JPG, PNG, WEBP, TIFF, PDF.',
        ]);

        $this->uploadingPhoto = true;
        $this->uploadError = null;

        try {
            // Delete old photo if exists
            if ($this->experiment->photo_path) {
                Storage::disk('local')->delete($this->experiment->photo_path);
            }

            // Store new photo
            $photoPath = $this->photo->store('experiments', 'local');

            // Update experiment record
            $this->experiment->update(['photo_path' => $photoPath]);

            // Refresh the experiment data
            $this->experiment = $this->experiment->fresh();

            $this->photo = null;
            $this->uploadingPhoto = false;

            session()->flash('message', 'Photo uploaded successfully!');

            // Dispatch event to clear file input
            $this->dispatch('photo-uploaded');

        } catch (\Exception $e) {
            $this->uploadingPhoto = false;
            $this->uploadError = 'Failed to upload photo: '.$e->getMessage();
        }
    }

    public function deletePhoto()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to delete the photo for this experiment.');

            return;
        }

        try {
            if ($this->experiment->photo_path) {
                Storage::disk('local')->delete($this->experiment->photo_path);
                $this->experiment->update(['photo_path' => null]);
                $this->experiment = $this->experiment->fresh();
                session()->flash('message', 'Photo deleted successfully!');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete photo: '.$e->getMessage());
        }
    }

    public function cancelPhotoSelection()
    {
        $this->photo = null;
        $this->uploadError = null;
        $this->dispatch('photo-cancelled');
    }

    public function editExperiment()
    {
        // For now, redirect to experiments list since there's no edit route
        // In the future, this could redirect to an edit form
        session()->flash('message', 'Edit functionality is not yet implemented. You can edit experiments from the experiments list.');

        return redirect('/experiments/list');
    }

    public function exportData()
    {
        // This will use the existing exportExperiment method
        return $this->exportExperiment();
    }

    public function deleteExperiment()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to delete this experiment.');

            return;
        }

        try {
            // Delete the experiment
            $this->experiment->delete();

            session()->flash('message', 'Experiment deleted successfully!');

            // Redirect to experiments list
            return redirect('/experiments/list');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete experiment: '.$e->getMessage());
        }
    }

    public function exportExperiment()
    {
        // Export functionality
        $data = [
            'experiment_code' => $this->experiment->code,
            'content_type' => $this->experiment->experiments_content_type,
            'content_code' => $this->experiment->experiments_content->code ?? 'N/A',
            'protocol' => $this->experiment->protocols->name ?? 'N/A',
            'protocol_type' => $this->experiment->protocols->techniques->type ?? 'N/A',
            'pathogen' => $this->experiment->pathogens->species ?? 'N/A',
            'outcome_discrete' => $this->experiment->outcome_discrete ?? 'N/A',
            'outcome_quant' => $this->experiment->outcome_quant ?? 'N/A',
            'date_tested' => $this->experiment->date_tested ? Carbon::parse($this->experiment->date_tested)->format('Y-m-d') : 'N/A',
            'performed_by' => $this->experiment->people->first_name.' '.$this->experiment->people->last_name ?? 'N/A',
            'performed_at' => $this->experiment->laboratories->name ?? 'N/A',
            'project' => $this->experiment->projects->title ?? 'N/A',
        ];

        // Add sample-specific details based on content type
        if ($this->experiment->experiments_content) {
            $content = $this->experiment->experiments_content;

            switch ($this->experiment->experiments_content_type) {
                case 'App\Models\AnimalSamples':
                    $data['sample_details'] = [
                        'animal_code' => $content->animals->code ?? 'N/A',
                        'animal_species' => $content->animals->animal_species->name_common
                            ?? $content->animals->animal_species->name_scientific
                            ?? 'N/A',
                        'sample_type' => $content->sample_types->name ?? 'N/A',
                        'date_collected' => $content->date_collected ? Carbon::parse($content->date_collected)->format('Y-m-d') : 'N/A',
                        'collection_site' => $content->sampling_sites->name ?? 'N/A',
                        'collected_by' => $content->people->first_name.' '.$content->people->last_name ?? 'N/A',
                    ];
                    break;

                case 'App\Models\HumanSamples':
                    $data['sample_details'] = [
                        'human_code' => $content->humans->code ?? 'N/A',
                        'sample_type' => $content->sample_types->name ?? 'N/A',
                        'date_collected' => $content->date_collected ? Carbon::parse($content->date_collected)->format('Y-m-d') : 'N/A',
                        'collection_site' => $content->laboratories->name ?? 'N/A',
                        'collected_by' => $content->people->first_name.' '.$content->people->last_name ?? 'N/A',
                    ];
                    break;

                case 'App\Models\ParasiteSamples':
                    $data['sample_details'] = [
                        'parasite_species' => $content->parasites->parasite_species->name_scientific ?? 'N/A',
                        'sample_type' => $content->parasite_sample_types->name ?? 'N/A',
                        'date_processed' => $content->date_processed ? Carbon::parse($content->date_processed)->format('Y-m-d') : 'N/A',
                        'processed_by' => $content->people->first_name.' '.$content->people->last_name ?? 'N/A',
                        'processing_lab' => $content->laboratories->name ?? 'N/A',
                    ];
                    break;

                case 'App\Models\NucleicAcids':
                    $data['sample_details'] = [
                        'nucleic_acid_type' => $content->type ?? 'N/A',
                        'date_extracted' => $content->date_extracted ? Carbon::parse($content->date_extracted)->format('Y-m-d') : 'N/A',
                        'volume' => $content->volume ?? 'N/A',
                        'extracted_by' => $content->people->first_name.' '.$content->people->last_name ?? 'N/A',
                        'extraction_lab' => $content->laboratories->name ?? 'N/A',
                    ];
                    break;

                case 'App\Models\EnvironmentSamples':
                    $data['sample_details'] = [
                        'sample_type' => $content->environment_sample_types->name ?? 'N/A',
                        'date_collected' => $content->date_collected ? Carbon::parse($content->date_collected)->format('Y-m-d') : 'N/A',
                        'collection_site' => $content->sampling_sites->name ?? 'N/A',
                        'collected_by' => $content->people->first_name.' '.$content->people->last_name ?? 'N/A',
                    ];
                    break;

                case 'App\Models\Cultures':
                    $data['sample_details'] = [
                        'culture_type' => $content->type ?? 'N/A',
                        'date_created' => $content->date_created ? Carbon::parse($content->date_created)->format('Y-m-d') : 'N/A',
                        'created_by' => $content->people->first_name.' '.$content->people->last_name ?? 'N/A',
                        'lab' => $content->laboratories->name ?? 'N/A',
                    ];
                    break;

                case 'App\Models\Pools':
                    $data['sample_details'] = [
                        'pool_type' => $content->type ?? 'N/A',
                        'date_created' => $content->date_created ? Carbon::parse($content->date_created)->format('Y-m-d') : 'N/A',
                        'created_by' => $content->people->first_name.' '.$content->people->last_name ?? 'N/A',
                        'lab' => $content->laboratories->name ?? 'N/A',
                    ];
                    break;
            }
        }

        // Generate CSV content
        $csvContent = $this->generateCsvContent($data);

        // Return CSV download
        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, 'experiment_'.$this->experiment->code.'_'.date('Y-m-d').'.csv');
    }

    private function generateCsvContent($data)
    {
        $csv = [];

        // Add main experiment data
        $csv[] = ['Experiment Information'];
        $csv[] = ['Code', $data['experiment_code']];
        $csv[] = ['Content Type', $data['content_type']];
        $csv[] = ['Content Code', $data['content_code']];
        $csv[] = ['Protocol', $data['protocol']];
        $csv[] = ['Protocol Type', $data['protocol_type']];
        $csv[] = ['Pathogen', $data['pathogen']];
        $csv[] = ['Outcome (Categorical)', $data['outcome_discrete']];
        $csv[] = ['Outcome (Quantitative)', $data['outcome_quant']];
        $csv[] = ['Date Tested', $data['date_tested']];
        $csv[] = ['Performed By', $data['performed_by']];
        $csv[] = ['Performed At', $data['performed_at']];
        $csv[] = ['Project', $data['project']];

        // Add sample details if available
        if (isset($data['sample_details'])) {
            $csv[] = []; // Empty row
            $csv[] = ['Sample Details'];
            foreach ($data['sample_details'] as $key => $value) {
                $csv[] = [ucwords(str_replace('_', ' ', $key)), $value];
            }
        }

        // Convert to CSV string
        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvString = stream_get_contents($output);
        fclose($output);

        return $csvString;
    }

    public function render()
    {
        if (! $this->canView) {
            return view('livewire.experiment-profile', [
                'experiment' => null,
                'relatedData' => ['tubes' => [], 'nucleic_acids' => []],
                'photo' => null,
                'uploadingPhoto' => false,
                'uploadError' => null,
                'canView' => false,
                'canEdit' => false,
                'unauthorizedMessage' => $this->unauthorizedMessage,
            ]);
        }

        $relatedData = $this->getRelatedData();

        return view('livewire.experiment-profile', [
            'experiment' => $this->experiment,
            'relatedData' => $relatedData,
            'photo' => $this->photo,
            'uploadingPhoto' => $this->uploadingPhoto,
            'uploadError' => $this->uploadError,
            'canView' => $this->canView,
            'canEdit' => $this->canEdit,
            'unauthorizedMessage' => $this->unauthorizedMessage,
        ]);
    }

    protected function getRelatedData()
    {
        if (! $this->experiment->experiments_content) {
            return ['tubes' => [], 'nucleic_acids' => []];
        }

        $contentType = $this->experiment->experiments_content_type;
        $contentId = $this->experiment->experiments_content->id;

        // Get related tubes
        $tubes = Tubes::where('tubes_content_type', $contentType)
            ->where('tubes_content_id', $contentId)
            ->where('projects_id', session('selected_project_id'))
            ->get(['code', 'purpose', 'state'])
            ->toArray();

        // Get related nucleic acids
        $nucleicAcids = [];
        if (in_array($contentType, [
            'App\\Models\\AnimalSamples',
            'App\\Models\\HumanSamples',
            'App\\Models\\ParasiteSamples',
            'App\\Models\\EnvironmentSamples',
            'App\\Models\\Cultures',
            'App\\Models\\Pools',
        ])) {
            $nucleicAcids = NucleicAcids::where('nucleic_content_type', $contentType)
                ->where('nucleic_content_id', $contentId)
                ->where('projects_id', session('selected_project_id'))
                ->get(['code', 'type', 'volume', 'date_extracted'])
                ->toArray();
        }

        return [
            'tubes' => $tubes,
            'nucleic_acids' => $nucleicAcids,
        ];
    }
}
