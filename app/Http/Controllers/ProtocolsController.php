<?php

namespace App\Http\Controllers;

use App\Models\Projects;
use App\Models\Protocols;
use App\Models\Techniques;
use App\Services\ExperimentsService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as LaravelValidator;

class ProtocolsController extends Controller
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

    public function store(): RedirectResponse
    {
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);
        $project_code = $project->code;

        $rules = [
            'protocol_name' => 'string|max:100|required',
            'protocol_new' => 'required|string|max:255',
            'technique_new' => 'nullable|string|max:255', // required when creating a new technique (validated conditionally below)
            'pathogens_protocol' => 'required|array|min:1',
            'pathogens_protocol.*' => 'integer|exists:pathogens,id',
            'ref_new' => 'required|array|min:1',
            'ref_new.*' => 'integer|exists:studies,id',
            'protocol_pdf' => 'nullable|file|mimes:pdf,doc,docx|max:51200', // 50MB max
        ];

        $validator = Validator::make(request()->all(), $rules);

        $this->validateTechniqueTypeWhenCreatingNewTechnique($validator);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {
            $techniques_id = $this->service->check_or_create(
                Techniques::class,
                ['name' => request('protocol_new')],
                ['type' => request('technique_new')]
            );

            // Use a transaction to ensure atomicity
            DB::transaction(function () use ($projectId, $project_code, $techniques_id) {
                // Find max serial safely (lexicographic order breaks after ...-PR-9).
                $maxSerial = Protocols::query()
                    ->where('code', 'like', $project_code.'-PR-%')
                    ->pluck('code')
                    ->map(function (string $code): int {
                        if (preg_match('/-PR-(\d+)$/', $code, $matches) === 1) {
                            return (int) $matches[1];
                        }

                        return 0;
                    })
                    ->max();

                $newSerial = ((int) ($maxSerial ?? 0)) + 1;

                // Generate the new code
                $pr_code = $project_code.'-PR-'.$newSerial;

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

                $protocol->studies()->attach(request('ref_new'));
                $protocol->pathogens()->attach(request('pathogens_protocol'));

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
        } catch (UniqueConstraintViolationException $e) {
            report($e);

            session()->flash('error', 'This protocol already exists (duplicate code or name).');

            return back()->withInput();
        } catch (\Throwable $e) {
            report($e);

            session()->flash('error', config('app.debug') ? $e->getMessage() : 'An error occurred while registering the protocol. Please try again.');

            return back()->withInput();
        }
    }

    private function validateTechniqueTypeWhenCreatingNewTechnique(LaravelValidator $validator): void
    {
        $validator->sometimes(
            'technique_new',
            ['required', 'string', 'max:255'],
            function ($input): bool {
                $name = trim((string) ($input->protocol_new ?? ''));
                if ($name === '') {
                    return false;
                }

                return ! Techniques::query()->where('name', $name)->exists();
            }
        );
    }
}
