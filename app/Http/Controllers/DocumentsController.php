<?php

namespace App\Http\Controllers;

use App\Models\Documents;
use App\Models\Projects;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentsController extends Controller
{
    private function authorizeProjectAdmin(): void
    {
        $projectId = session('selected_project_id');

        if (! AdminAccess::hasProjectAdminAccess(Auth::user(), $projectId !== null ? (int) $projectId : null)) {
            abort(403, 'You do not have admin permission to manage documents in this project.');
        }
    }

    private function findProjectDocument(int $documentId): Documents
    {
        return Documents::query()
            ->where('projects_id', session('selected_project_id'))
            ->findOrFail($documentId);
    }

    /**
     * @return array<string, mixed>
     */
    private function documentValidationRules(bool $requireFile = true): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'document_date' => 'nullable|date',
            'parent_id' => 'nullable|exists:documents,id',
        ];

        if ($requireFile) {
            $rules['file'] = 'required|file|mimes:pdf,doc,docx,ppt,pptx,txt|max:56320';
        } else {
            $rules['file'] = 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,txt|max:56320';
        }

        return $rules;
    }

    public function index()
    {
        // Check if a project is selected
        if (! session()->has('selected_project_id')) {
            return redirect()->route('profile.projects')
                ->with('error', 'Please select a project to view documents.');
        }

        $projectId = session('selected_project_id');
        $project = Projects::with(['documents' => function ($query) {
            $query->orderBy('document_date', 'desc')
                ->with('amendments'); // Eager load amendments
        }])->findOrFail($projectId);

        // Group documents by type
        $documentsByType = $project->documents->groupBy('type');

        return view('documents', compact('project', 'documentsByType'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeProjectAdmin();

        $validated = $request->validate($this->documentValidationRules());

        try {
            $projectId = session('selected_project_id');
            $project = Projects::findOrFail($projectId);

            // Store file
            $file = $request->file('file');
            $filePath = $file->store('documents', 'local');

            // Create document
            $document = $project->documents()->create([
                'title' => $validated['title'],
                'type' => $validated['type'],
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'description' => $validated['description'] ?? null,
                'document_date' => $validated['document_date'] ?? null,
                'parent_id' => $validated['parent_id'] ?? null,
            ]);

            return back()->with('success', 'Document added successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'An error occurred while adding the document.');
        }
    }

    public function update(Request $request, Documents $document): RedirectResponse
    {
        $this->authorizeProjectAdmin();

        $document = $this->findProjectDocument($document->id);

        $validated = $request->validate($this->documentValidationRules(requireFile: false));

        try {
            $updateData = [
                'title' => $validated['title'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'document_date' => $validated['document_date'] ?? null,
                'parent_id' => $validated['type'] === 'Amendment' ? ($validated['parent_id'] ?? null) : null,
            ];

            if ($request->hasFile('file')) {
                if (Storage::disk('local')->exists($document->file_path)) {
                    Storage::disk('local')->delete($document->file_path);
                }

                $file = $request->file('file');
                $updateData['file_path'] = $file->store('documents', 'local');
                $updateData['file_name'] = $file->getClientOriginalName();
                $updateData['mime_type'] = $file->getClientMimeType();
            }

            $document->update($updateData);

            return back()->with('success', 'Document updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'An error occurred while updating the document.');
        }
    }

    public function destroy(Documents $document): RedirectResponse
    {
        $this->authorizeProjectAdmin();

        $document = $this->findProjectDocument($document->id);

        try {
            if (Storage::disk('local')->exists($document->file_path)) {
                Storage::disk('local')->delete($document->file_path);
            }

            $document->delete();

            return back()->with('success', 'Document deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred while deleting the document.');
        }
    }
}
