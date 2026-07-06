<?php

namespace App\Livewire;

use App\Models\MetaAnimal;
use App\Models\MetaEnvironment;
use App\Models\MetaHuman;
use App\Models\MetaParasite;
use App\Models\Studies;
use App\Support\ProjectPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class StudyProfile extends PlainComponent
{
    use WithFileUploads;
    use WithPagination;

    public $study;

    public $id;

    public $document;

    public $uploadingDocument = false;

    public $uploadError = null;

    public bool $canEdit = false;

    public string $metaTab = 'animals';

    public int $metaPerPage = 25;

    /**
     * @var array<string, mixed>
     */
    public array $metaFilters = [
        'country' => '',
        'location' => '',
        'species' => '',
        'pathogen' => '',
        'sample_type' => '',
        'technique' => '',
        'tested_min' => '',
        'pos_min' => '',
    ];

    /**
     * @var array<string, array{count:int,tested:int,pos:int,rate:float|null,countries:int,pathogens:int}>
     */
    public array $metaStats = [];

    public $editingValues = [
        'title' => '',
        'doi' => '',
        'publication_year' => '',
        'study_design' => '',
        'risk_bias' => '',
        'abstract' => '',
        'sampling_strategy' => '',
    ];

    public function mount($id)
    {
        $this->id = $id;

        $this->study = Studies::query()
            ->with(['protocols', 'protocols.techniques', 'user', 'user.people'])
            ->withCount(['meta_animals', 'meta_humans', 'meta_environments', 'meta_parasites'])
            ->findOrFail($id);

        $this->canEdit = $this->canManageStudy();

        $this->metaStats = $this->computeMetaStats();

        $this->metaTab = collect([
            'animals' => (int) ($this->study->meta_animals_count ?? 0),
            'humans' => (int) ($this->study->meta_humans_count ?? 0),
            'environments' => (int) ($this->study->meta_environments_count ?? 0),
            'parasites' => (int) ($this->study->meta_parasites_count ?? 0),
        ])->sortDesc()->keys()->first() ?? 'animals';
    }

    public function canManageStudy(): bool
    {
        if ($this->isGuestMode()) {
            return false;
        }

        if (! $this->userCanWriteModule('literature')) {
            return false;
        }

        $projectId = $this->selectedProjectId();
        $user = Auth::user();
        if ($projectId !== null && $user) {
            $permission = ProjectPermission::membership($user, $projectId)['permission'] ?? null;
            if ($permission === 'admin') {
                return true;
            }
        }

        $userId = Auth::id();
        if (! $userId) {
            return false;
        }

        $ownerId = $this->study->users_id ?? null;

        return $ownerId !== null && (int) $ownerId === (int) $userId;
    }

    public function updatedMetaTab(): void
    {
        $this->resetPage('metaPage');
    }

    public function updatedMetaPerPage(): void
    {
        $this->resetPage('metaPage');
    }

    public function updatedMetaFilters(): void
    {
        $this->resetPage('metaPage');
    }

    public function getMetaRowsProperty()
    {
        $query = match ($this->metaTab) {
            'humans' => MetaHuman::query()
                ->where('studies_id', $this->id)
                ->with(['countries', 'pathogens', 'sample_types', 'techniques', 'risk_factors', 'clinical_signs', 'lesions']),
            'animals' => MetaAnimal::query()
                ->where('studies_id', $this->id)
                ->with(['countries', 'pathogens', 'sample_types', 'techniques', 'risk_factors', 'animal_species', 'clinical_signs', 'lesions']),
            'environments' => MetaEnvironment::query()
                ->where('studies_id', $this->id)
                ->with(['countries', 'pathogens', 'environment_sample_types', 'techniques', 'risk_factors']),
            'parasites' => MetaParasite::query()
                ->where('studies_id', $this->id)
                ->with(['countries', 'pathogens', 'parasite_sample_types', 'parasite_species', 'techniques', 'risk_factors']),
            default => MetaAnimal::query()
                ->where('studies_id', $this->id)
                ->with(['countries', 'pathogens', 'sample_types', 'techniques', 'risk_factors', 'animal_species', 'clinical_signs', 'lesions']),
        };

        if (filled($this->metaFilters['location'] ?? null)) {
            $query->where('location', 'like', '%'.trim((string) $this->metaFilters['location']).'%');
        }

        if (filled($this->metaFilters['country'] ?? null)) {
            $country = trim((string) $this->metaFilters['country']);
            $query->whereHas('countries', function ($q) use ($country) {
                $q->where('name', 'like', '%'.$country.'%');
            });
        }

        if (filled($this->metaFilters['pathogen'] ?? null)) {
            $pathogen = trim((string) $this->metaFilters['pathogen']);
            $query->whereHas('pathogens', function ($q) use ($pathogen) {
                $q->where('species', 'like', '%'.$pathogen.'%');
            });
        }

        if (filled($this->metaFilters['technique'] ?? null)) {
            $technique = trim((string) $this->metaFilters['technique']);
            $query->whereHas('techniques', function ($q) use ($technique) {
                $q->where('name', 'like', '%'.$technique.'%')
                    ->orWhere('type', 'like', '%'.$technique.'%');
            });
        }

        if (filled($this->metaFilters['sample_type'] ?? null)) {
            $sampleType = trim((string) $this->metaFilters['sample_type']);
            if ($this->metaTab === 'environments') {
                $query->whereHas('environment_sample_types', function ($q) use ($sampleType) {
                    $q->where('name', 'like', '%'.$sampleType.'%');
                });
            } elseif ($this->metaTab === 'parasites') {
                $query->whereHas('parasite_sample_types', function ($q) use ($sampleType) {
                    $q->where('name', 'like', '%'.$sampleType.'%');
                });
            } else {
                $query->whereHas('sample_types', function ($q) use ($sampleType) {
                    $q->where('name', 'like', '%'.$sampleType.'%');
                });
            }
        }

        if (filled($this->metaFilters['species'] ?? null) && $this->metaTab === 'animals') {
            $species = trim((string) $this->metaFilters['species']);
            $query->whereHas('animal_species', function ($q) use ($species) {
                $q->where('name_common', 'like', '%'.$species.'%')
                    ->orWhere('name', 'like', '%'.$species.'%');
            });
        }

        if (filled($this->metaFilters['tested_min'] ?? null)) {
            $query->where('tested_n', '>=', (int) $this->metaFilters['tested_min']);
        }

        if (filled($this->metaFilters['pos_min'] ?? null)) {
            $query->where('pos_n', '>=', (int) $this->metaFilters['pos_min']);
        }

        return $query->orderByDesc('id')->paginate($this->metaPerPage, ['*'], 'metaPage');
    }

    public function startEdit($field): void
    {
        if (! $this->canEdit) {
            return;
        }

        $this->study = $this->study->fresh(['protocols', 'protocols.techniques']);
        $this->editingValues[$field] = (string) ($this->study->$field ?? '');
        $this->dispatch('start-edit', field: $field);
    }

    public function saveEdit($field): void
    {
        if (! $this->canEdit) {
            return;
        }

        try {
            $this->validate([
                'editingValues.title' => 'nullable|string|max:2000',
                'editingValues.doi' => 'nullable|string|max:255',
                'editingValues.publication_year' => 'nullable|integer|min:1500|max:2200',
                'editingValues.study_design' => 'nullable|string|max:255',
                'editingValues.risk_bias' => 'nullable|string|max:255',
                'editingValues.abstract' => 'nullable|string|max:20000',
                'editingValues.sampling_strategy' => 'nullable|string|max:20000',
            ]);

            $value = $this->editingValues[$field];
            if ($value === '') {
                $value = null;
            }

            $this->study->update([$field => $value]);
            $this->study = $this->study->fresh(['protocols', 'protocols.techniques'])->loadCount(['meta_animals', 'meta_humans', 'meta_environments', 'meta_parasites']);

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

    public function cancelEdit($field): void
    {
        $this->study = $this->study->fresh(['protocols', 'protocols.techniques'])->loadCount(['meta_animals', 'meta_humans', 'meta_environments', 'meta_parasites']);
        $this->editingValues[$field] = '';
        $this->dispatch('cancel-edit', field: $field);
    }

    public function uploadDocument()
    {
        if (! $this->canManageStudy()) {
            $this->uploadError = 'Only the user who registered this study can upload or replace the manuscript.';
            $this->document = null;

            return;
        }

        if (! $this->document) {
            $this->uploadError = 'Please select a document first.';
            $this->dispatch('show-error', message: $this->uploadError);

            return;
        }

        // Check file size (50MB = 52428800 bytes)
        if ($this->document->getSize() > 52428800) {
            $this->uploadError = 'The selected file is too large. Maximum allowed size is 50MB.';
            $this->document = null;

            $this->dispatch('show-error', message: $this->uploadError);

            return;
        }

        $this->validate([
            'document' => 'required|file|mimes:pdf,doc,docx|max:51200', // 50MB max
        ]);

        $this->uploadingDocument = true;
        $this->uploadError = null;

        try {
            // Delete old document if exists
            if ($this->study->pdf_path) {
                Storage::disk('local')->delete($this->study->pdf_path);
            }

            // Store new document
            $documentPath = $this->document->store('study-documents', 'local');

            // Update study record
            $this->study->update(['pdf_path' => $documentPath]);

            // Refresh the study data
            $this->study = $this->study->fresh();

            $this->document = null;
            $this->uploadingDocument = false;

            session()->flash('message', 'Document uploaded successfully!');
            $this->dispatch('show-success', message: 'Document uploaded successfully!');

            // Dispatch event to clear file input
            $this->dispatch('document-uploaded');

        } catch (\Exception $e) {
            $this->uploadingDocument = false;
            $this->uploadError = 'Failed to upload document: '.$e->getMessage();
            $this->dispatch('show-error', message: $this->uploadError);
        }
    }

    public function deleteDocument()
    {
        if (! $this->canManageStudy()) {
            session()->flash('error', 'Only the user who registered this study can delete the manuscript.');

            return;
        }

        try {
            if ($this->study->pdf_path) {
                Storage::disk('local')->delete($this->study->pdf_path);
                $this->study->update(['pdf_path' => null]);
                $this->study = $this->study->fresh();
                session()->flash('message', 'Document deleted successfully!');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete document: '.$e->getMessage());
        }
    }

    public function cancelDocumentSelection()
    {
        $this->document = null;
        $this->uploadError = null;
        $this->dispatch('document-cancelled');
    }

    public function exportStudy()
    {
        // Export functionality
        $data = [
            'study_ref_key' => $this->study->ref_key,
            'title' => $this->study->title,
            'abstract' => $this->study->abstract,
            'doi' => $this->study->doi ?? 'N/A',
            'publication_year' => $this->study->publication_year ?? 'N/A',
            'study_design' => $this->study->study_design ?? 'N/A',
            'risk_bias' => $this->study->risk_bias ?? 'N/A',
            'sampling_strategy' => $this->study->sampling_strategy ?? 'N/A',
        ];

        // Generate CSV content
        $csvContent = $this->generateCsvContent($data);

        // Return CSV download
        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, 'study_'.$this->study->ref_key.'_'.date('Y-m-d').'.csv');
    }

    public function deleteStudy()
    {
        if (! $this->canManageStudy()) {
            session()->flash('error', 'Only the user who registered this study can delete it.');

            return;
        }

        try {
            // Delete the study
            $this->study->delete();

            session()->flash('message', 'Study deleted successfully!');

            // Redirect to studies gallery
            return redirect('/meta/gallery');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete study: '.$e->getMessage());
        }
    }

    private function generateCsvContent($data)
    {
        $csv = [];

        // Add main study data
        $csv[] = ['Study Information'];
        $csv[] = ['Reference Key', $data['study_ref_key']];
        $csv[] = ['Title', $data['title']];
        $csv[] = ['Abstract', $data['abstract']];
        $csv[] = ['DOI', $data['doi']];
        $csv[] = ['Publication Year', $data['publication_year']];
        $csv[] = ['Study Design', $data['study_design']];
        $csv[] = ['Risk of Bias', $data['risk_bias']];
        $csv[] = ['Sampling Strategy', $data['sampling_strategy']];

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
        return view('livewire.study-profile', [
            'study' => $this->study,
            'metaTab' => $this->metaTab,
            'metaRows' => $this->metaRows,
            'metaStats' => $this->metaStats,
            'canManageStudy' => $this->canManageStudy(),
        ]);
    }

    /**
     * @return array<string, array{count:int,tested:int,pos:int,rate:float|null,countries:int,pathogens:int}>
     */
    private function computeMetaStats(): array
    {
        $statsFor = function (string $modelClass): array {
            $base = $modelClass::query()->where('studies_id', $this->id);
            $count = (int) $base->count();
            $tested = (int) (clone $base)->sum('tested_n');
            $pos = (int) (clone $base)->sum('pos_n');
            $rate = $tested > 0 ? round(($pos / $tested) * 100, 1) : null;
            $countries = (int) (clone $base)->distinct()->count('countries_id');
            $pathogens = (int) (clone $base)->distinct()->count('pathogens_id');

            return [
                'count' => $count,
                'tested' => $tested,
                'pos' => $pos,
                'rate' => $rate,
                'countries' => $countries,
                'pathogens' => $pathogens,
            ];
        };

        return [
            'humans' => $statsFor(MetaHuman::class),
            'animals' => $statsFor(MetaAnimal::class),
            'environments' => $statsFor(MetaEnvironment::class),
            'parasites' => $statsFor(MetaParasite::class),
        ];
    }
}
