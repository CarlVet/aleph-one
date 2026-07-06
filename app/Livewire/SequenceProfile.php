<?php

namespace App\Livewire;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\Sequences;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

class SequenceProfile extends PlainComponent
{
    use WithFileUploads;

    public $sequence;

    public $code;

    public $fastaContent;

    public $fastaError;

    public $isTruncated = false;

    public $document;

    public $uploadingDocument = false;

    public $uploadError = null;

    public bool $canView = false;

    public bool $canEdit = false;

    public string $unauthorizedMessage = '';

    public array $editingValues = [
        'method' => '',
        'instrument' => '',
        'date_sequenced' => '',
    ];

    public function mount($code)
    {
        $this->code = $code;
        $this->checkAuthorization();

        if (! $this->canView) {
            return;
        }

        $this->sequence = $this->loadSequence();

        // Load FASTA content
        $this->refreshFastaContent();
    }

    private function checkAuthorization(): void
    {
        $selectedProjectId = $this->selectedProjectId();
        $sequence = Sequences::where('code', $this->code)->first();

        if (! $sequence) {
            $this->canView = false;
            $this->unauthorizedMessage = 'Sequence not found.';

            return;
        }

        if (! $selectedProjectId) {
            if ((bool) ($sequence->is_private ?? false)) {
                $this->canView = false;
                $this->unauthorizedMessage = 'This sequence is private and cannot be viewed in guest mode.';

                return;
            }

            $this->canEdit = false;
            $this->canView = true;

            return;
        }

        if ((int) $sequence->projects_id !== (int) $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'You are not authorized to view the profile of this sequence because it does not belong to your selected project.';

            return;
        }

        $this->canEdit = $this->userCanMutateOwnedRecord((int) ($sequence->people_id ?? 0), 'nucleic_acids');
        $this->canView = true;
    }

    private function loadSequence(): Sequences
    {
        return Sequences::with([
            'nucleic_acids.tubes',
            'nucleic_acids.nucleic_content' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Experiments::class => [
                        'pathogens',
                        'people',
                        'laboratories',
                        'protocols',
                        'experiments_content' => function (MorphTo $innerMorphTo): void {
                            $innerMorphTo->morphWith([
                                NucleicAcids::class => [
                                    'tubes',
                                    'nucleic_content' => function (MorphTo $sourceMorphTo): void {
                                        $sourceMorphTo->morphWith([
                                            HumanSamples::class => ['humans.countries', 'sample_types', 'sampling_sites', 'tubes'],
                                            AnimalSamples::class => ['animals.animal_species', 'sample_types', 'sampling_sites', 'tubes'],
                                            EnvironmentSamples::class => ['environment_sample_types', 'sampling_sites', 'tubes'],
                                            ParasiteSamples::class => ['parasites.parasite_species', 'parasite_sample_types', 'tubes'],
                                            Cultures::class => ['laboratories', 'tubes'],
                                            Pools::class => ['pool_contents.samples', 'tubes'],
                                        ]);
                                    },
                                ],
                            ]);
                        },
                    ],
                    HumanSamples::class => ['humans.countries', 'sample_types', 'sampling_sites', 'tubes'],
                    AnimalSamples::class => ['animals.animal_species', 'sample_types', 'sampling_sites', 'tubes'],
                    EnvironmentSamples::class => ['environment_sample_types', 'sampling_sites', 'tubes'],
                    ParasiteSamples::class => ['parasites.parasite_species', 'parasite_sample_types', 'tubes'],
                    Cultures::class => ['laboratories', 'tubes'],
                    Pools::class => ['pool_contents.samples', 'tubes'],
                ]);
            },
            'people',
            'laboratories',
            'projects',
        ])->where('code', $this->code)->firstOrFail();
    }

    public function uploadDocument()
    {
        if (! $this->canEdit) {
            $this->uploadError = 'You do not have permission to upload files for sequences.';
            $this->document = null;
            $this->dispatch('show-error', message: $this->uploadError);

            return;
        }

        if (! $this->document) {
            $this->uploadError = 'Please select a file first.';
            $this->dispatch('show-error', message: $this->uploadError);

            return;
        }

        // Check file size (50MB = 52428800 bytes)
        if ($this->document->getSize() > 52428800) {
            $this->uploadError = 'File size exceeds 50MB limit.';
            $this->document = null;
            $this->dispatch('show-error', message: $this->uploadError);

            return;
        }

        $allowedExtensions = ['fa', 'fasta', 'fq', 'fastq', 'txt'];
        $extension = strtolower((string) $this->document->getClientOriginalExtension());
        if (! in_array($extension, $allowedExtensions, true)) {
            $this->uploadError = 'Invalid file format. Supported formats: FASTA (.fa/.fasta) and FASTQ (.fq/.fastq).';
            $this->document = null;
            $this->dispatch('show-error', message: $this->uploadError);

            return;
        }

        $this->validate([
            'document' => 'required|file|max:51200', // 50MB max
        ]);

        $this->uploadingDocument = true;
        $this->uploadError = null;

        try {
            // Always reload the model on action requests (Livewire may hydrate public model properties as arrays).
            $this->sequence = $this->loadSequence();

            // Delete old file if exists
            if ($this->sequence->fasta_path) {
                Storage::disk('local')->delete($this->sequence->fasta_path);
            }

            // Store new file
            $filePath = $this->document->store('sequence-files', 'local');

            // Update sequence record
            $this->sequence->update(['fasta_path' => $filePath]);

            // Refresh the sequence data
            $this->sequence = $this->loadSequence();

            // Refresh FASTA content after upload
            $this->refreshFastaContent();

            $this->document = null;
            $this->uploadingDocument = false;

            session()->flash('message', 'FASTA file uploaded successfully!');
            $this->dispatch('show-success', message: 'Sequence file uploaded successfully!');

            // Dispatch event to clear file input
            $this->dispatch('document-uploaded');

        } catch (\Throwable $e) {
            $this->uploadingDocument = false;
            $this->uploadError = 'Failed to upload file: '.$e->getMessage();
            $this->dispatch('show-error', message: $this->uploadError);
        }
    }

    public function deleteDocument()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to delete files from sequences.');
            $this->dispatch('show-error', message: 'You do not have permission to delete files from sequences.');

            return;
        }

        try {
            $this->sequence = $this->loadSequence();

            if ($this->sequence->fasta_path) {
                Storage::disk('local')->delete($this->sequence->fasta_path);
                $this->sequence->update(['fasta_path' => null]);
                $this->sequence = $this->loadSequence();

                // Clear FASTA content after deletion
                $this->fastaContent = null;
                $this->fastaError = null;
                $this->isTruncated = false;

                session()->flash('message', 'FASTA file deleted successfully!');
                $this->dispatch('show-success', message: 'Sequence file deleted successfully!');
            }
        } catch (\Throwable $e) {
            session()->flash('error', 'Failed to delete file: '.$e->getMessage());
            $this->dispatch('show-error', message: 'Failed to delete file: '.$e->getMessage());
        }
    }

    private function refreshFastaContent()
    {
        // Reset FASTA-related properties
        $this->fastaContent = null;
        $this->fastaError = null;
        $this->isTruncated = false;

        // Try to get FASTA content
        if ($this->sequence->fasta_path) {
            try {
                // Try with public disk first
                if (Storage::disk('local')->exists($this->sequence->fasta_path)) {
                    $content = Storage::disk('local')->get($this->sequence->fasta_path);
                }
                // Then try with local disk
                elseif (Storage::disk('local')->exists($this->sequence->fasta_path)) {
                    $content = Storage::disk('local')->get($this->sequence->fasta_path);
                }
                // If neither works, try without specifying disk
                elseif (Storage::exists($this->sequence->fasta_path)) {
                    $content = Storage::get($this->sequence->fasta_path);
                }

                if ($content) {
                    $this->isTruncated = strlen($content) > 1000;
                    $this->fastaContent = $this->isTruncated ? substr($content, 0, 1000)."\n\n... (content truncated, download full file to see complete sequence)" : $content;
                } else {
                    $this->fastaError = 'File exists but is empty or could not be read. Tried paths: '.
                        'public: '.Storage::disk('local')->path($this->sequence->fasta_path).', '.
                        'local: '.Storage::disk('local')->path($this->sequence->fasta_path);
                }
            } catch (\Exception $e) {
                $this->fastaError = 'Error reading file: '.$e->getMessage().
                    "\nTried paths: ".
                    'public: '.Storage::disk('local')->path($this->sequence->fasta_path).', '.
                    'local: '.Storage::disk('local')->path($this->sequence->fasta_path);
            }
        }
    }

    public function cancelDocumentSelection()
    {
        $this->document = null;
        $this->uploadError = null;
        $this->dispatch('document-cancelled');
    }

    public function startEdit(string $field): void
    {
        if (! $this->canEdit) {
            $this->dispatch('show-error', message: 'You do not have permission to edit this sequence.');

            return;
        }

        $this->sequence = $this->loadSequence();

        if ($field === 'date_sequenced') {
            $this->editingValues[$field] = $this->sequence->date_sequenced?->format('Y-m-d') ?? '';
        } else {
            $this->editingValues[$field] = (string) ($this->sequence->$field ?? '');
        }

        $this->dispatch('start-edit', field: $field);
    }

    public function saveEdit(string $field): void
    {
        if (! $this->canEdit) {
            $this->dispatch('show-error', message: 'You do not have permission to edit this sequence.');

            return;
        }

        try {
            $this->validate([
                'editingValues.method' => 'nullable|string|max:255',
                'editingValues.instrument' => 'nullable|string|max:255',
                'editingValues.date_sequenced' => 'nullable|date',
            ]);

            $value = $this->editingValues[$field] ?? null;
            if ($value === '') {
                $value = null;
            }

            $this->sequence->update([$field => $value]);
            $this->sequence = $this->loadSequence();

            $this->editingValues[$field] = '';
            $this->dispatch('save-edit', field: $field);
            $this->dispatch('show-success', message: ucfirst(str_replace('_', ' ', $field)).' updated successfully!');
        } catch (ValidationException $e) {
            $messages = collect($e->errors())->flatten()->toArray();
            $this->dispatch('show-error', message: implode("\n", $messages));
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: 'Failed to update '.str_replace('_', ' ', $field).': '.$e->getMessage());
        }
    }

    public function cancelEdit(string $field): void
    {
        $this->sequence = $this->loadSequence();
        $this->editingValues[$field] = '';
        $this->dispatch('cancel-edit', field: $field);
    }

    public function deleteSequence()
    {
        if (! $this->canEdit) {
            $this->dispatch('show-error', message: 'You do not have permission to delete this sequence.');

            return;
        }

        try {
            $sequence = Sequences::where('code', $this->code)->firstOrFail();

            if ($sequence->fasta_path) {
                Storage::disk('local')->delete($sequence->fasta_path);
            }

            $sequence->delete();
            session()->flash('message', 'Sequence deleted successfully!');

            return redirect('/samples/nucleic/sequences/list');
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: 'Failed to delete sequence: '.$e->getMessage());
        }
    }

    public function render()
    {
        if (! $this->canView) {
            return view('livewire.sequence-profile', [
                'sequence' => null,
                'fastaContent' => null,
                'fastaError' => null,
                'isTruncated' => false,
                'canView' => false,
                'canEdit' => false,
                'unauthorizedMessage' => $this->unauthorizedMessage,
            ]);
        }

        return view('livewire.sequence-profile', [
            'sequence' => $this->sequence,
            'fastaContent' => $this->fastaContent,
            'fastaError' => $this->fastaError,
            'isTruncated' => $this->isTruncated,
            'canView' => true,
            'canEdit' => $this->canEdit,
            'unauthorizedMessage' => '',
        ]);
    }
}
