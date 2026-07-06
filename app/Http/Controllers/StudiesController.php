<?php

namespace App\Http\Controllers;

use App\Models\Studies;
use App\Services\ExperimentsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class StudiesController extends Controller
{
    protected $service;

    public function __construct(ExperimentsService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        return view('studies.create', $this->service->assign());
    }

    public function store()
    {
        $rules = [
            'study_doi' => 'string|max:100|required',
            'study_ref' => 'string|max:100|required',
            'study_title' => 'string|max:200|required',
            'study_abstract' => 'string|max:3000|nullable',
            'study_year' => 'required|integer|min:1800|max:2200',
            'study_design' => 'required',
            'study_pdf' => 'nullable|file|mimes:pdf|max:10240', // Max 10MB
        ];

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {
            $pdfPath = null;
            if (request()->hasFile('study_pdf')) {
                $pdf = request()->file('study_pdf');
                $pdfPath = $pdf->store('studies', 'local');
            }

            $study = Studies::create([
                'doi' => request('study_doi'),
                'ref_key' => request('study_ref'),
                'title' => request('study_title'),
                'abstract' => request('study_abstract'),
                'publication_year' => request('study_year'),
                'study_design' => request('study_design'),
                'users_id' => Auth::id(),
                'pdf_path' => $pdfPath,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Flash success message to the session
            session()->flash('success', 'Study registered successfully!');

            // Get the authenticated user
            $user = Auth::user();

            // Create notification for the new study
            NotificationController::create(
                'study_created',
                'New study',
                $user->people->first_name.' registered a study.',
                "/studies/{$study->id}",
                session('selected_project_id')
            );

            return back();
        } catch (\Exception $e) {
            // Handle any exceptions during registration
            session()->flash('error', 'An error occurred: '.$e->getMessage());

            return back()->withInput();
        }
    }
}
