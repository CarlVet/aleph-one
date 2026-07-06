<?php

namespace App\Http\Controllers;

use App\Models\NucleicAcids;
use App\Models\Projects;
use App\Models\Sequences;
use App\Models\Tubes;
use App\Services\SequencesService;
use App\Support\ProjectPermission;
use App\Support\SubProjectFlag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SequencesController extends Controller
{
    protected $service;

    public function __construct(SequencesService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        $projectId = (int) session('selected_project_id');
        $user = Auth::user();

        return view('samples.nucleic_acids.sequences.create', array_merge($this->service->dataForCreate(), [
            'can_assign_registrar' => $user ? ProjectPermission::canAssignRegistrar($user, $projectId) : false,
            'locked_registrar_people_id' => $user ? ProjectPermission::currentRegistrarPeopleId($user) : null,
            'sub_project_options' => SubProjectFlag::optionsForUser($user, $projectId),
        ]));
    }

    public function store(Request $request)
    {
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);
        $project_code = $project->code;

        $rules = [
            'nucleic_tube_id' => 'required|array',
            'nucleic_tube_id.*' => 'exists:tubes,id',
            'length' => 'required|integer|min:1',
            'method' => 'required|string',
            'instrument' => 'required|string',
            'date_sequenced' => 'required|date',
            'people_id' => 'required|exists:people,id',
            'laboratories_id' => 'required|exists:laboratories,id',
            'sub_project_id' => 'nullable|integer|exists:sub_projects,id',
            'fasta_file' => 'nullable|file|mimes:txt|max:10240', // Changed to accept txt files since fasta files are text files
        ];

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {
            $selectedSubProjectId = request('sub_project_id') ? (int) request('sub_project_id') : null;
            if (! SubProjectFlag::isSelectableByUser(Auth::user(), (int) $projectId, $selectedSubProjectId)) {
                session()->flash('error', 'The selected sub-project is not available for your user.');

                return back()->withInput();
            }

            $sequencerPeopleId = $this->resolveRegistrarPeopleId('people_id');

            // Create sequence records for each selected nucleic acid
            foreach ($request->nucleic_tube_id as $nucleicTubeId) {

                // First check if the tube exists at all
                $tube = Tubes::find($nucleicTubeId);
                if (! $tube) {
                    session()->flash('error', "Tube with ID {$nucleicTubeId} does not exist.");

                    return back()->withInput();
                }

                // Then check if it has nucleic acid content
                $nucleicTube = Tubes::whereHas('tubes_content', function ($query) {
                    $query->where('tubes_content_type', NucleicAcids::class);
                })->where('id', $nucleicTubeId)
                    ->first();

                // Check if the tube exists and has nucleic acid content
                if (! $nucleicTube || ! $nucleicTube->tubes_content) {
                    session()->flash('error', "Tube with ID {$nucleicTubeId} does not contain nucleic acids. Tube type: ".($tube->tubes_content_type ?? 'null'));

                    return back()->withInput();
                }

                // Get all existing culture codes for this project
                $existingSeCodes = Sequences::where('projects_id', $projectId)
                    ->where('code', 'like', $project_code.'-SE-%')
                    ->pluck('code');

                $usedNumbers = $existingSeCodes->map(function ($code) {
                    preg_match('/-SE-(\d+)$/', $code, $matches);

                    return isset($matches[1]) ? (int) $matches[1] : null;
                })->filter()->sort()->values();

                $newSerial = 1;
                foreach ($usedNumbers as $num) {
                    if ($num != $newSerial) {
                        break;
                    }
                    $newSerial++;
                }

                $se_code = $project_code.'-SE-'.$newSerial;

                $sequence = Sequences::create([
                    'code' => $se_code,
                    'nucleic_acids_id' => $nucleicTube->tubes_content->id,
                    'length' => $request->length,
                    'method' => $request->method,
                    'instrument' => $request->instrument,
                    'date_sequenced' => $request->date_sequenced,
                    'people_id' => $sequencerPeopleId,
                    'laboratories_id' => $request->laboratories_id,
                    'projects_id' => $project->id,
                ]);
                SubProjectFlag::assign($sequence, $selectedSubProjectId);

                // Handle fasta file upload if present
                if ($request->hasFile('fasta_file')) {
                    $file = $request->file('fasta_file');
                    $filename = $se_code.'.fasta'; // Always save as .fasta
                    $path = $file->storeAs('sequences', $filename, 'local');
                    $sequence->update(['fasta_path' => $path]);
                }
            }

            $count = count($request->nucleic_tube_id);
            $user = Auth::user();

            NotificationController::create(
                'sequences_created',
                'New Sequences',
                $user->people->first_name.' sequenced '.$count.' sample'.($count > 1 ? 's.' : '.'),
                '/samples/nucleic/list',
                $projectId
            );

            return back()
                ->with('success', 'Sequence(s) created successfully.');

        } catch (\Exception $e) {
            // Handle any exceptions during registration
            session()->flash('error', 'An error occurred: '.$e->getMessage());

            return back()->withInput();
        }

    }

    public function uploadFile(Request $request, string $code): RedirectResponse
    {
        $projectId = (int) session('selected_project_id');

        $sequence = Sequences::query()
            ->where('code', $code)
            ->firstOrFail();

        if ((int) $sequence->projects_id !== $projectId) {
            abort(403);
        }

        $request->validate([
            'sequence_file' => 'required|file|max:51200', // 50MB
        ]);

        $file = $request->file('sequence_file');
        if (! $file) {
            return back()->with('error', 'Please select a file first.');
        }

        $allowedExtensions = ['fa', 'fasta', 'fq', 'fastq', 'txt'];
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($extension, $allowedExtensions, true)) {
            return back()->with('error', 'Invalid file format. Supported formats: FASTA (.fa/.fasta) and FASTQ (.fq/.fastq).');
        }

        try {
            if ($sequence->fasta_path) {
                Storage::disk('local')->delete($sequence->fasta_path);
            }

            $path = $file->storeAs('sequence-files', $sequence->code.'.'.$extension, 'local');
            $sequence->update(['fasta_path' => $path]);

            return back()->with('success', 'Sequence file uploaded successfully!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to upload file: '.$e->getMessage());
        }
    }

    public function deleteFile(string $code): RedirectResponse
    {
        $projectId = (int) session('selected_project_id');

        $sequence = Sequences::query()
            ->where('code', $code)
            ->firstOrFail();

        if ((int) $sequence->projects_id !== $projectId) {
            abort(403);
        }

        try {
            if ($sequence->fasta_path) {
                Storage::disk('local')->delete($sequence->fasta_path);
            }

            $sequence->update(['fasta_path' => null]);

            return back()->with('success', 'Sequence file deleted successfully!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to delete file: '.$e->getMessage());
        }
    }

    private function resolveRegistrarPeopleId(string $requestKey): ?int
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        $projectId = (int) session('selected_project_id');
        if (ProjectPermission::canAssignRegistrar($user, $projectId)) {
            return request($requestKey) ? (int) request($requestKey) : null;
        }

        return ProjectPermission::currentRegistrarPeopleId($user);
    }
}
