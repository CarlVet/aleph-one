<?php

namespace App\Livewire;

use App\Models\Pathogens;
use App\Models\ProtocolComments;
use App\Models\Protocols;
use App\Models\Studies;
use App\Models\Techniques;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProtocolProfile extends PlainComponent
{
    use WithFileUploads;
    use WithPagination;

    public $protocol;

    public $code;

    public $document;

    public $uploadingDocument = false;

    public $uploadError = null;

    public bool $showComments = true;

    public string $newComment = '';

    /**
     * @var array<int, string>
     */
    public array $replyBodies = [];

    /**
     * @var array<int, bool>
     */
    public array $expandedThreads = [];

    /**
     * @var array<int, bool>
     */
    public array $replyForms = [];

    public bool $showEditProtocolModal = false;

    public bool $showAssociateStudiesModal = false;

    public string $editProtocolName = '';

    public ?int $editTechniqueId = null;

    /**
     * @var array<int, int|string>
     */
    public array $selectedStudyIds = [];

    public string $studyRefKeyFilter = '';

    public string $studyTitleFilter = '';

    public string $studyYearFilter = '';

    public string $studySortBy = 'publication_year';

    public string $studySortDirection = 'desc';

    public function mount($code)
    {
        $this->code = $code;
        $this->protocol = Protocols::with([
            'techniques',
            'pathogens',
            'studies',
            'experiments',
            'experiments.people',
            'experiments.laboratories',
            'user',
        ])->where('code', $code)->firstOrFail();
    }

    public function canManageProtocol(): bool
    {
        if ($this->isGuestMode()) {
            return false;
        }

        $userId = Auth::id();
        if (! $userId) {
            return false;
        }

        $ownerId = $this->protocol->users_id ?? null;
        if ($ownerId === null) {
            return false;
        }

        return (int) $ownerId === (int) $userId;
    }

    public function canComment(): bool
    {
        return Auth::check();
    }

    public function toggleComments(): void
    {
        $this->showComments = ! $this->showComments;
    }

    public function toggleThread(int $commentId): void
    {
        $this->expandedThreads[$commentId] = ! ($this->expandedThreads[$commentId] ?? false);
    }

    public function toggleReplyForm(int $commentId): void
    {
        $this->replyForms[$commentId] = ! ($this->replyForms[$commentId] ?? false);
    }

    public function getTopLevelCommentsProperty()
    {
        return ProtocolComments::query()
            ->where('protocols_id', $this->protocol->id)
            ->whereNull('parent_id')
            ->with(['user.people'])
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'commentsPage');
    }

    public function uploadDocument()
    {
        if (! $this->canManageProtocol()) {
            $this->uploadError = 'You do not have permission to upload a document for this protocol.';

            return;
        }

        if (! $this->document) {
            $this->uploadError = 'Please select a document first.';

            return;
        }

        // Check file size (50MB = 52428800 bytes)
        if ($this->document->getSize() > 52428800) {
            $this->uploadError = 'File size exceeds 50MB limit.';
            $this->document = null;

            return;
        }

        $this->validate([
            'document' => 'required|file|mimes:pdf,doc,docx|max:51200', // 50MB max
        ]);

        $this->uploadingDocument = true;
        $this->uploadError = null;

        try {
            // Delete old file if exists
            if ($this->protocol->pdf_path) {
                Storage::disk('local')->delete($this->protocol->pdf_path);
            }

            // Store new file
            $filePath = $this->document->store('protocol-documents', 'local');

            // Update protocol record
            $this->protocol->update(['pdf_path' => $filePath]);

            // Refresh the protocol data
            $this->protocol = $this->protocol->fresh();

            $this->document = null;
            $this->uploadingDocument = false;

            session()->flash('message', 'Protocol document uploaded successfully!');

            // Dispatch event to clear file input
            $this->dispatch('document-uploaded');

        } catch (\Exception $e) {
            $this->uploadingDocument = false;
            $this->uploadError = 'Failed to upload document: '.$e->getMessage();
        }
    }

    public function deleteDocument()
    {
        if (! $this->canManageProtocol()) {
            session()->flash('error', 'You do not have permission to delete this protocol document.');

            return;
        }

        try {
            if ($this->protocol->pdf_path) {
                Storage::disk('local')->delete($this->protocol->pdf_path);
                $this->protocol->update(['pdf_path' => null]);
                $this->protocol = $this->protocol->fresh();
                session()->flash('message', 'Protocol document deleted successfully!');
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

    public function openEditProtocolModal(): void
    {
        if (! $this->canManageProtocol()) {
            session()->flash('error', 'You do not have permission to edit this protocol.');

            return;
        }

        $this->editProtocolName = (string) ($this->protocol->name ?? '');
        $this->editTechniqueId = $this->protocol->techniques_id ? (int) $this->protocol->techniques_id : null;
        $this->showEditProtocolModal = true;
    }

    public function closeEditProtocolModal(): void
    {
        $this->showEditProtocolModal = false;
    }

    public function saveProtocolInfo(): void
    {
        if (! $this->canManageProtocol()) {
            session()->flash('error', 'You do not have permission to edit this protocol.');

            return;
        }

        $validated = $this->validate([
            'editProtocolName' => 'required|string|max:100',
            'editTechniqueId' => 'required|integer|exists:techniques,id',
        ]);

        $this->protocol->update([
            'name' => $validated['editProtocolName'],
            'techniques_id' => (int) $validated['editTechniqueId'],
        ]);

        $this->protocol = Protocols::with([
            'techniques',
            'pathogens',
            'studies',
            'experiments',
            'experiments.people',
            'experiments.laboratories',
            'user',
        ])->where('code', $this->code)->firstOrFail();

        $this->showEditProtocolModal = false;
        session()->flash('message', 'Protocol information updated successfully.');
    }

    public function openAssociateStudiesModal(): void
    {
        if (! $this->canManageProtocol()) {
            session()->flash('error', 'You do not have permission to update associated studies.');

            return;
        }

        $this->selectedStudyIds = $this->protocol->studies->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $this->studyRefKeyFilter = '';
        $this->studyTitleFilter = '';
        $this->studyYearFilter = '';
        $this->studySortBy = 'publication_year';
        $this->studySortDirection = 'desc';
        $this->showAssociateStudiesModal = true;
    }

    public function closeAssociateStudiesModal(): void
    {
        $this->showAssociateStudiesModal = false;
    }

    public function updatingStudyRefKeyFilter(): void
    {
        $this->resetPage('associateStudiesPage');
    }

    public function updatingStudyTitleFilter(): void
    {
        $this->resetPage('associateStudiesPage');
    }

    public function updatingStudyYearFilter(): void
    {
        $this->resetPage('associateStudiesPage');
    }

    public function sortStudies(string $column): void
    {
        if (! in_array($column, ['ref_key', 'title', 'publication_year'], true)) {
            return;
        }

        if ($this->studySortBy === $column) {
            $this->studySortDirection = $this->studySortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->studySortBy = $column;
            $this->studySortDirection = $column === 'publication_year' ? 'desc' : 'asc';
        }

        $this->resetPage('associateStudiesPage');
    }

    public function saveAssociatedStudies(): void
    {
        if (! $this->canManageProtocol()) {
            session()->flash('error', 'You do not have permission to update associated studies.');

            return;
        }

        $studyIds = collect($this->selectedStudyIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($studyIds === []) {
            session()->flash('error', 'Select at least one study.');

            return;
        }

        $validStudyIds = Studies::query()
            ->whereIn('id', $studyIds)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($validStudyIds === []) {
            session()->flash('error', 'No valid studies were selected.');

            return;
        }

        $this->protocol->studies()->syncWithoutDetaching($validStudyIds);

        $this->protocol = Protocols::with([
            'techniques',
            'pathogens',
            'studies',
            'experiments',
            'experiments.people',
            'experiments.laboratories',
            'user',
        ])->where('code', $this->code)->firstOrFail();

        $this->showAssociateStudiesModal = false;
        session()->flash('message', 'Associated studies registered successfully.');
    }

    public function render()
    {
        $topLevelComments = null;
        $commentChildren = [];
        $commentsCount = null;

        if ($this->showComments) {
            $topLevelComments = $this->topLevelComments;
            $rootIds = $topLevelComments->getCollection()->pluck('id')->all();
            $commentChildren = $this->commentChildrenForRoots($rootIds);
            $commentsCount = ProtocolComments::query()
                ->where('protocols_id', $this->protocol->id)
                ->count();
        }

        return view('livewire.protocol-profile', [
            'protocol' => $this->protocol,
            'canManageProtocol' => $this->canManageProtocol(),
            'availableStudies' => $this->availableStudies,
            'availableTechniques' => Techniques::query()->orderBy('name')->get(),
            'exp_protocols' => Protocols::query()
                ->with(['techniques', 'studies', 'pathogens'])
                ->orderBy('name')
                ->get(),
            'pathogens' => Pathogens::query()->orderBy('species')->get(),
            'protocol_pathogen_map' => Protocols::with('pathogens')->get()->mapWithKeys(function ($protocol) {
                return [
                    $protocol->name => $protocol->pathogens->map(function ($pathogen) {
                        return [
                            'id' => $pathogen->id,
                            'species' => $pathogen->species,
                        ];
                    })->toArray(),
                ];
            }),
            'canComment' => $this->canComment(),
            'showComments' => $this->showComments,
            'topLevelComments' => $topLevelComments,
            'commentChildren' => $commentChildren,
            'commentsCount' => $commentsCount,
        ]);
    }

    public function getAvailableStudiesProperty()
    {
        return Studies::query()
            ->when(
                $this->studyRefKeyFilter !== '',
                fn ($query) => $query->where('ref_key', 'like', '%'.$this->studyRefKeyFilter.'%')
            )
            ->when(
                $this->studyTitleFilter !== '',
                fn ($query) => $query->where('title', 'like', '%'.$this->studyTitleFilter.'%')
            )
            ->when(
                $this->studyYearFilter !== '',
                fn ($query) => $query->whereRaw('CAST(publication_year AS TEXT) like ?', ['%'.$this->studyYearFilter.'%'])
            )
            ->orderBy($this->studySortBy, $this->studySortDirection)
            ->orderBy('ref_key')
            ->paginate(10, ['*'], 'associateStudiesPage');
    }

    public function exportProtocol()
    {
        // Export functionality
        $data = [
            'protocol_code' => $this->protocol->code,
            'protocol_name' => $this->protocol->name,
            'technique_name' => $this->protocol->techniques->name ?? 'N/A',
            'technique_type' => $this->protocol->techniques->type ?? 'N/A',
            'associated_pathogens' => $this->protocol->pathogens->pluck('species')->implode(', ') ?: 'N/A',
            'associated_studies' => $this->protocol->studies->pluck('ref_key')->implode(', ') ?: 'N/A',
        ];

        // Generate CSV content
        $csvContent = $this->generateCsvContent($data);

        // Return CSV download
        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, 'protocol_'.$this->protocol->code.'_'.date('Y-m-d').'.csv');
    }

    public function deleteProtocol()
    {
        if (! $this->canManageProtocol()) {
            session()->flash('error', 'You do not have permission to delete this protocol.');

            return;
        }

        try {
            // Delete the protocol
            $this->protocol->delete();

            session()->flash('message', 'Protocol deleted successfully!');

            // Redirect to experiments list
            return redirect('/experiments/list');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete protocol: '.$e->getMessage());
        }
    }

    private function generateCsvContent($data)
    {
        $csv = [];

        // Add main protocol data
        $csv[] = ['Protocol Information'];
        $csv[] = ['Code', $data['protocol_code']];
        $csv[] = ['Name', $data['protocol_name']];
        $csv[] = ['Technique Name', $data['technique_name']];
        $csv[] = ['Technique Type', $data['technique_type']];
        $csv[] = ['Associated Pathogens', $data['associated_pathogens']];
        $csv[] = ['Associated Studies', $data['associated_studies']];

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

    public function addComment(?int $parentId = null): void
    {
        if (! $this->canComment()) {
            session()->flash('error', 'You do not have permission to comment.');

            return;
        }

        $body = $parentId ? trim((string) ($this->replyBodies[$parentId] ?? '')) : trim($this->newComment);

        if ($body === '') {
            session()->flash('error', 'Comment cannot be empty.');

            return;
        }

        if (mb_strlen($body) > 5000) {
            session()->flash('error', 'Comment is too long (max 5000 characters).');

            return;
        }

        ProtocolComments::create([
            'protocols_id' => $this->protocol->id,
            'users_id' => Auth::id(),
            'parent_id' => $parentId,
            'body' => $body,
        ]);

        if ($parentId) {
            unset($this->replyBodies[$parentId]);
        } else {
            $this->newComment = '';
        }

        $this->showComments = true;
        $this->resetPage('commentsPage');
        $this->dispatch('comment-added');
    }

    /**
     * @param  array<int, int>  $rootIds
     * @return array<int|null, Collection<int, ProtocolComments>>
     */
    public function commentChildrenForRoots(array $rootIds): array
    {
        if ($rootIds === []) {
            return [];
        }

        $descendants = collect();
        $pendingParentIds = $rootIds;

        while ($pendingParentIds !== []) {
            $batch = ProtocolComments::query()
                ->where('protocols_id', $this->protocol->id)
                ->whereIn('parent_id', $pendingParentIds)
                ->with(['user.people'])
                ->orderBy('created_at')
                ->get();

            if ($batch->isEmpty()) {
                break;
            }

            $descendants = $descendants->merge($batch);
            $pendingParentIds = $batch->pluck('id')->all();
        }

        /** @var array<int|null, Collection<int, ProtocolComments>> $children */
        $children = $descendants->groupBy(fn (ProtocolComments $c) => $c->parent_id)->all();

        return $children;
    }
}
