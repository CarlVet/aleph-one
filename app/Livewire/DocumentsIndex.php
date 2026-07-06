<?php

namespace App\Livewire;

use App\Models\Documents;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocumentsIndex extends Component
{
    use WithFileUploads;

    public $search = '';

    public $filterType = '';

    public $showUploadModal = false;

    public $uploadTitle = '';

    public $uploadDescription = '';

    public $uploadTags = '';

    public $uploadFile;

    public array $selectedDocuments = [];

    protected $rules = [
        'uploadTitle' => 'required|string|max:255',
        'uploadDescription' => 'nullable|string|max:1000',
        'uploadTags' => 'nullable|string|max:255',
        'uploadFile' => 'required|file|max:10240', // 10MB max
    ];

    public function mount()
    {
        if (! session()->has('selected_project_id')) {
            return redirect()->route('profile.projects')
                ->with('error', 'Please select a project to view documents.');
        }
    }

    public function showUploadModal()
    {
        $this->showUploadModal = true;
        $this->resetUploadForm();
    }

    public function resetUploadForm()
    {
        $this->uploadTitle = '';
        $this->uploadDescription = '';
        $this->uploadTags = '';
        $this->uploadFile = null;
    }

    public function uploadDocument()
    {
        $this->validate();

        try {
            $filePath = $this->uploadFile->store('documents', 'local');

            Documents::create([
                'title' => $this->uploadTitle,
                'description' => $this->uploadDescription,
                'tags' => $this->uploadTags,
                'file_path' => $filePath,
                'file_name' => $this->uploadFile->getClientOriginalName(),
                'file_size' => $this->uploadFile->getSize(),
                'mime_type' => $this->uploadFile->getMimeType(),
                'projects_id' => session('selected_project_id'),
                'uploaded_by_id' => Auth::user()->people->id,
            ]);

            $this->showUploadModal = false;
            $this->resetUploadForm();

            session()->flash('message', 'Document uploaded successfully!');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to upload document: '.$e->getMessage());
        }
    }

    public function deleteDocument($documentId)
    {
        try {
            $document = Documents::findOrFail($documentId);

            // Delete file from storage
            if (Storage::disk('local')->exists($document->file_path)) {
                Storage::disk('local')->delete($document->file_path);
            }

            $document->delete();

            session()->flash('message', 'Document deleted successfully!');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete document: '.$e->getMessage());
        }
    }

    public function deleteSelected(): void
    {
        $selectedIds = collect($this->selectedDocuments)
            ->filter(fn ($checked): bool => (bool) $checked)
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($selectedIds->isEmpty()) {
            session()->flash('error', 'Please select at least one document.');

            return;
        }

        $peopleId = (int) (Auth::user()?->people?->id ?? 0);
        $documents = Documents::query()
            ->where('projects_id', session('selected_project_id'))
            ->whereIn('id', $selectedIds->all())
            ->get();

        $deleted = 0;
        foreach ($documents as $document) {
            if ((int) $document->uploaded_by_id !== $peopleId) {
                continue;
            }

            if (Storage::disk('local')->exists($document->file_path)) {
                Storage::disk('local')->delete($document->file_path);
            }

            $document->delete();
            $deleted++;
        }

        $this->selectedDocuments = [];
        session()->flash(
            $deleted > 0 ? 'message' : 'error',
            $deleted > 0 ? "{$deleted} selected document(s) deleted successfully." : 'No selected documents could be deleted.'
        );
    }

    public function exportDocuments()
    {
        $documents = $this->getFilteredDocuments();

        $csvContent = "Title,Description,Type,Size,Uploaded By,Upload Date,Tags\n";

        foreach ($documents as $document) {
            $csvContent .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s"'."\n",
                $document->title,
                $document->description ?? '',
                $document->type ?? 'Unknown',
                $document->size_formatted ?? '',
                $document->uploaded_by ? $document->uploaded_by->first_name.' '.$document->uploaded_by->last_name : 'Unknown',
                $document->created_at->format('Y-m-d'),
                $document->tags ?? ''
            );
        }

        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, 'documents_export_'.date('Y-m-d').'.csv');
    }

    private function getFilteredDocuments()
    {
        $query = Documents::with(['uploaded_by'])
            ->where('projects_id', session('selected_project_id'));

        if ($this->search) {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        return $query->orderBy('created_at', 'desc')->get()->map(function ($document) {
            $document->size_formatted = $this->formatFileSize($document->file_size);
            $extension = pathinfo($document->file_path, PATHINFO_EXTENSION);
            $document->type = $this->getFileType($extension);

            return $document;
        });
    }

    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        } else {
            return $bytes.' bytes';
        }
    }

    private function getFileType($extension)
    {
        $extension = strtolower($extension);

        if (in_array($extension, ['pdf'])) {
            return 'pdf';
        } elseif (in_array($extension, ['doc', 'docx'])) {
            return 'word';
        } elseif (in_array($extension, ['xls', 'xlsx'])) {
            return 'excel';
        } else {
            return 'other';
        }
    }

    public function render()
    {
        $documents = $this->getFilteredDocuments();

        return view('livewire.documents-index', [
            'documents' => $documents,
        ]);
    }
}
