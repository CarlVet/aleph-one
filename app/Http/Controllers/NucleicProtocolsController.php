<?php

namespace App\Http\Controllers;

use App\Models\Projects;
use App\Models\Protocols;
use App\Models\Techniques;
use App\Services\ExperimentsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NucleicProtocolsController extends Controller
{
    protected $service;

    public function __construct(ExperimentsService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        return view('protocols.create', $this->service->assign());
    }

    public function store()
    {
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);
        $project_code = $project->code;

        $rules = [
            'protocol_name' => 'string|max:100|required',
            'protocol_new' => 'required',
            'protocol_pdf' => 'nullable|file|mimes:pdf,doc,docx|max:10240', // 10MB max, added doc/docx support
        ];

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {
            $techniques_id = $this->service->check_or_create(
                Techniques::class,
                ['name' => request('protocol_new')],
                ['type' => 'Nucleic Acids Extraction and Purification']
            );

            // Use a transaction to ensure atomicity
            DB::transaction(function () use ($projectId, $project_code, $techniques_id) {
                // Generate the next available code for this project.
                // NOTE: Sorting by string can collide (PR-10 can sort "before" PR-2, etc).
                $existingCodes = Protocols::query()
                    ->where('code', 'like', $project_code.'-PR-%')
                    ->pluck('code');

                $maxSerial = 0;
                foreach ($existingCodes as $code) {
                    if (preg_match('/-PR-(\d+)$/', (string) $code, $matches)) {
                        $maxSerial = max($maxSerial, (int) $matches[1]);
                    }
                }

                $newSerial = $maxSerial + 1;
                $pr_code = $project_code.'-PR-'.$newSerial;

                // Extra safety: keep incrementing until free (handles gaps / concurrency).
                while (Protocols::query()->where('code', $pr_code)->exists()) {
                    $newSerial++;
                    $pr_code = $project_code.'-PR-'.$newSerial;
                }

                // Handle file upload
                $filePath = null;
                if (request()->hasFile('protocol_pdf')) {
                    $file = request()->file('protocol_pdf');
                    $filePath = $file->store('protocols', 'local');
                }

                $protocol = Protocols::create([
                    'code' => $pr_code,
                    'name' => request('protocol_name'),
                    'techniques_id' => $techniques_id,
                    'users_id' => Auth::id(),
                    'pdf_path' => $filePath,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $studyIds = array_values(array_filter((array) request('ref_new', [])));
                if ($studyIds) {
                    $protocol->studies()->attach($studyIds);
                }

                $pathogenIds = array_values(array_filter((array) request('pathogens_protocol', [])));
                if ($pathogenIds) {
                    $protocol->pathogens()->attach($pathogenIds);
                }

                session()->flash('success', 'Protocol registered successfully!');

                // Get the authenticated user
                $user = Auth::user();

                // Create notification
                NotificationController::create(
                    'protocols_created',
                    'New Protocol',
                    $user->people->first_name.' registered a new protocol.',
                    "/protocols/{$protocol->code}",
                    $projectId
                );
            });

            return back();
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());

            return back()->withInput();
        }
    }
}
