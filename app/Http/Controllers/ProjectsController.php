<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Documents;
use App\Models\EnvironmentSamples;
use App\Models\Fundings;
use App\Models\Message;
use App\Models\People;
use App\Models\Projects;
use App\Models\User;
use App\Support\ProjectPermission;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProjectsController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $user = Auth::user();
        $person = $user->people;
        $allProjects = $person->projects()
            ->with(['people' => function ($query) {
                $query->select('people.*')
                    ->with('users:id,email')
                    ->withPivot('role', 'date_joined', 'permission');
            },
                'funding',
                'funding.recipient',
            ])
            ->withPivot('role', 'date_joined', 'permission')
            ->get();

        // Separate projects into active and completed
        $activeProjects = $allProjects->filter(function ($project) {
            // Project is active if:
            // 1. No end date is set (ongoing)
            // 2. End date is in the future
            return ! $project->date_end ||
                   Carbon::parse($project->date_end)->isFuture();
        });

        $completedProjects = $allProjects->filter(function ($project) {
            // Project is completed if end date is in the past
            return $project->date_end &&
                   Carbon::parse($project->date_end)->isPast();
        });

        return view('profile.projects', [
            'activeProjects' => $activeProjects,
            'completedProjects' => $completedProjects,
            'person' => $person,
        ]);
    }

    public function create()
    {
        $step = request('step', 1);
        $people = People::with('users')->get();

        // Clean up any abandoned project data if starting fresh
        if ($step == 1) {
            $this->cleanupAbandonedProject();
        }

        // Get next available project code for display
        $existingCodes = Projects::pluck('code')->toArray();
        $nextCode = $this->generateNextProjectCode($existingCodes);

        // Get team members for funding step
        $teamMembers = session('project.team', []);

        // Debug session data
        $sessionData = [
            'general' => session('project.general'),
            'team' => session('project.team'),
            'funding' => session('project.funding'),
            'documents' => session('project.documents'),
        ];

        Log::info('Projects create method called', [
            'step' => $step,
            'session_data' => $sessionData,
        ]);

        return view('projects.create', [
            'step' => $step,
            'people' => $people,
            'teamMembers' => $teamMembers,
            'nextCode' => $nextCode,
            'modulePermissionOptions' => ProjectPermission::moduleOptions(),
        ]);
    }

    public function markComplete(Request $request, Projects $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'date_end' => 'required|date',
        ]);

        $dateEnd = Carbon::parse($validated['date_end'])->startOfDay();

        if ($project->date_started && $dateEnd->lt(Carbon::parse($project->date_started)->startOfDay())) {
            return response()->json([
                'success' => false,
                'message' => 'Official end date cannot be before the start date.',
            ], 422);
        }

        $project->update([
            'status' => 'completed',
            'date_end' => $dateEnd->toDateString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project marked as completed.',
        ]);
    }

    private function cleanupAbandonedProject()
    {
        $documents = session('project.documents');
        if ($documents) {
            $this->cleanupTempFiles($documents);
        }

        // Clear all project session data
        session()->forget(['project.general', 'project.team', 'project.funding', 'project.documents']);
        Log::info('Cleaned up abandoned project data');
    }

    public function store(Request $request)
    {
        $step = $request->input('step', 1);

        Log::info('Project store method called', [
            'step' => $step,
            'request_data' => $request->all(),
        ]);

        switch ($step) {
            case 1:
                $validated = $request->validate([
                    'project_type' => 'required|string|max:255',
                    'start_date' => 'required|date',
                    'intended_end_date' => 'required|date|after:start_date',
                    'title' => 'required|string|max:255',
                    'description' => 'required|string',
                    'ethics_reference' => 'nullable|string|max:255',
                    'alias_code' => 'nullable|string|max:255',
                ]);

                session(['project.general' => $validated]);
                Log::info('Step 1 completed, general data stored in session', $validated);

                return redirect()->route('projects.create', ['step' => 2]);

            case 2:
                $validated = $request->validate([
                    'team_members' => 'required|array|min:1',
                    'team_members.*.title' => 'required|string|max:255',
                    'team_members.*.first_name' => 'required|string|max:255',
                    'team_members.*.last_name' => 'required|string|max:255',
                    'team_members.*.email' => 'required|email|max:255',
                    'team_members.*.role' => 'required|string|max:255',
                    'team_members.*.permission' => 'required|string|max:255',
                    'team_members.*.module_permissions' => 'nullable|array',
                    'team_members.*.module_permissions.*' => 'string|in:'.implode(',', array_keys(ProjectPermission::moduleOptions())),
                    'team_members.*.date_joined' => 'required|date',
                    'team_members.*.person_id' => 'nullable|exists:people,id',
                ]);

                // Ensure the submitting user is present and correctly linked to their People record
                $currentUser = Auth::user();
                $currentPeopleId = $currentUser && $currentUser->people ? $currentUser->people->id : null;
                if ($currentPeopleId) {
                    $teamMembers = $validated['team_members'];
                    $foundIndex = null;
                    foreach ($teamMembers as $idx => $member) {
                        if (
                            (isset($member['person_id']) && (int) $member['person_id'] === (int) $currentPeopleId) ||
                            (isset($member['email']) && strtolower($member['email']) === strtolower($currentUser->email))
                        ) {
                            $foundIndex = $idx;
                            break;
                        }
                    }
                    if ($foundIndex === null) {
                        // Prepend an entry for the current user if missing
                        array_unshift($teamMembers, [
                            'title' => $currentUser->people->title ?? '',
                            'first_name' => $currentUser->people->first_name ?? '',
                            'last_name' => $currentUser->people->last_name ?? '',
                            'email' => $currentUser->email,
                            'role' => 'Principal Investigator',
                            'permission' => 'admin',
                            'module_permissions' => [],
                            'date_joined' => now()->toDateString(),
                            'person_id' => $currentPeopleId,
                        ]);
                    } else {
                        // Force-link the person_id for the found entry
                        $teamMembers[$foundIndex]['person_id'] = $currentPeopleId;
                    }
                    $validated['team_members'] = $teamMembers;
                }

                session(['project.team' => $validated['team_members']]);
                Log::info('Step 2 completed, team data stored in session', $validated['team_members']);

                // Debug: Log each team member individually
                foreach ($validated['team_members'] as $index => $member) {
                    Log::info("Team member {$index} data:", $member);
                }

                return redirect()->route('projects.create', ['step' => 3]);

            case 3:
                // Check if any funding data was actually provided
                $hasFundingData = false;
                if ($request->has('funding_sources')) {
                    foreach ($request->funding_sources as $source) {
                        if (! empty($source['source']) || ! empty($source['recipient_id']) || ! empty($source['amount'])) {
                            $hasFundingData = true;
                            break;
                        }
                    }
                }

                if ($hasFundingData) {
                    $validated = $request->validate([
                        'funding_sources' => 'required|array',
                        'funding_sources.*.source' => 'required|string|max:255',
                        'funding_sources.*.recipient_id' => 'required|string', // Changed from exists:people,id to string
                        'funding_sources.*.amount' => 'required|numeric|min:0',
                        'funding_sources.*.currency' => 'required|string|max:3',
                        'funding_sources.*.reference' => 'nullable|string|max:255',
                        'funding_sources.*.start_date' => 'required|date',
                        'funding_sources.*.end_date' => 'required|date|after:funding_sources.*.start_date',
                    ]);
                    session(['project.funding' => $validated['funding_sources']]);
                    Log::info('Step 3 completed, funding data stored in session', $validated['funding_sources']);
                } else {
                    session(['project.funding' => []]);
                    Log::info('Step 3 completed, no funding data provided');
                }

                return redirect()->route('projects.create', ['step' => 4]);

            case 4:
                Log::info('Step 4 validation - request data:', $request->all());

                // Check if any document data was actually provided
                $hasDocumentData = false;
                if ($request->has('documents')) {
                    foreach ($request->documents as $document) {
                        if (! empty($document['title']) || ! empty($document['type']) || ! empty($document['document_date'])) {
                            $hasDocumentData = true;
                            break;
                        }
                    }
                }

                if ($hasDocumentData) {
                    $validated = $request->validate([
                        'documents' => 'required|array',
                        'documents.*.type' => 'required|string|max:255',
                        'documents.*.description' => 'nullable|string',
                        'documents.*.document_date' => 'required|date',
                        'documents.*.title' => 'required|string|max:255',
                        'documents.*.file' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:56320',
                    ]);

                    Log::info('Step 4 validation passed, validated data:', $validated);

                    // Store files temporarily and store file info in session
                    $documents = [];
                    foreach ($validated['documents'] as $index => $document) {
                        $fileInfo = null;

                        if (isset($document['file']) && $document['file']) {
                            // Store the file temporarily and get the path
                            $file = $document['file'];
                            $tempPath = $file->store('temp-documents', 'local');

                            $fileInfo = [
                                'temp_path' => $tempPath,
                                'original_name' => $file->getClientOriginalName(),
                                'mime_type' => $file->getMimeType(),
                                'size' => $file->getSize(),
                            ];

                            Log::info("File uploaded for document {$index}:", $fileInfo);
                        } else {
                            Log::info("No file uploaded for document {$index}");
                        }

                        $documents[] = [
                            'type' => $document['type'],
                            'description' => $document['description'] ?? null,
                            'file_info' => $fileInfo,
                            'document_date' => $document['document_date'],
                            'title' => $document['title'],
                        ];
                    }
                    session(['project.documents' => $documents]);
                    Log::info('Step 4 completed, documents data stored in session', $documents);
                } else {
                    session(['project.documents' => []]);
                    Log::info('Step 4 completed, no documents data provided');
                }

                return redirect()->route('projects.review');

            case 'final':
                Log::info('Final step called, proceeding to create project');

                return $this->createFinalProject();

            default:
                Log::warning('Invalid step provided', ['step' => $step]);

                return redirect('/my-projects');
        }
    }

    private function cleanupTempFiles($documents)
    {
        if (! $documents) {
            return;
        }

        foreach ($documents as $document) {
            if (isset($document['file_info']['temp_path'])) {
                $tempPath = $document['file_info']['temp_path'];
                if (Storage::disk('local')->exists($tempPath)) {
                    Storage::disk('local')->delete($tempPath);
                    Log::info('Cleaned up temp file', ['temp_path' => $tempPath]);
                }
            }
        }
    }

    private function createFinalProject()
    {
        $general = session('project.general');
        $team = session('project.team');
        $funding = session('project.funding');
        $documents = session('project.documents');

        if (! $general || ! $team) {
            return redirect()->route('projects.create', ['step' => 1])
                ->with('error', 'Please complete all required steps before creating the project.');
        }

        DB::beginTransaction();

        try {
            // Generate unique project code
            $existingCodes = Projects::pluck('code')->toArray();

            // Find the next available code
            $newCode = $this->generateNextProjectCode($existingCodes);

            // Create project with correct field mapping
            $projectData = [
                'code' => $newCode,
                'alias_code' => $general['alias_code'] ?? null,
                'type' => $general['project_type'],
                'title' => $general['title'],
                'description' => $general['description'],
                'ethics_ref' => $general['ethics_reference'],
                'date_started' => $general['start_date'],
                'date_end_intended' => $general['intended_end_date'],
            ];

            $project = Projects::create($projectData);

            // Create team members using the pivot table
            foreach ($team as $index => $member) {
                Log::info("Processing team member {$index}", $member);

                // Validate required fields
                if (empty($member['title']) || empty($member['first_name']) || empty($member['last_name']) || empty($member['email'])) {
                    Log::error("Team member {$index} has missing required fields", $member);
                    throw new \Exception("Team member {$index} has missing required fields");
                }

                if (isset($member['person_id']) && $member['person_id']) {
                    // Attach existing person to project
                    Log::info("Attaching existing person {$member['person_id']} to project");
                    $existingPerson = People::find($member['person_id']);
                    if ($existingPerson && empty($existingPerson->email) && ! empty($member['email'])) {
                        $existingPerson->update(['email' => $member['email']]);
                    }
                    $project->people()->attach($member['person_id'], [
                        'role' => $member['role'],
                        'permission' => $member['permission'],
                        'module_permissions' => json_encode($this->normalizeModulePermissionsForPermission((string) $member['permission'], (array) ($member['module_permissions'] ?? []))),
                        'date_joined' => $member['date_joined'],
                    ]);
                    $this->notifyProjectMemberAdded($project, People::find($member['person_id']), (string) $member['role']);
                } else {
                    // Check if person already exists by email
                    $existingPerson = People::where('email', $member['email'])->first();
                    if (! $existingPerson) {
                        // Fallback: find via Users table to avoid creating duplicate People
                        $userForEmail = User::where('email', $member['email'])->first();
                        if ($userForEmail && $userForEmail->people) {
                            $existingPerson = $userForEmail->people;
                            Log::info("Resolved person via users table for email {$member['email']}", ['person_id' => $existingPerson->id]);
                        }
                    }

                    if ($existingPerson) {
                        // Use existing person
                        Log::info("Using existing person with ID {$existingPerson->id} for email {$member['email']}");
                        if (empty($existingPerson->email) && ! empty($member['email'])) {
                            $existingPerson->update(['email' => $member['email']]);
                        }
                        $project->people()->attach($existingPerson->id, [
                            'role' => $member['role'],
                            'permission' => $member['permission'],
                            'module_permissions' => json_encode($this->normalizeModulePermissionsForPermission((string) $member['permission'], (array) ($member['module_permissions'] ?? []))),
                            'date_joined' => $member['date_joined'],
                        ]);
                        $this->notifyProjectMemberAdded($project, $existingPerson, (string) $member['role']);
                    } else {
                        // Create new person and attach to project
                        Log::info("Creating new person for team member {$index}");
                        $newPerson = People::create([
                            'title' => $member['title'],
                            'first_name' => $member['first_name'],
                            'last_name' => $member['last_name'],
                            'email' => $member['email'],
                        ]);

                        Log::info("Created new person with ID {$newPerson->id}");
                        $project->people()->attach($newPerson->id, [
                            'role' => $member['role'],
                            'permission' => $member['permission'],
                            'module_permissions' => json_encode($this->normalizeModulePermissionsForPermission((string) $member['permission'], (array) ($member['module_permissions'] ?? []))),
                            'date_joined' => $member['date_joined'],
                        ]);
                        $this->notifyProjectMemberAdded($project, $newPerson, (string) $member['role']);
                    }
                }
            }

            // Create funding sources (optional)
            if (! empty($funding)) {
                foreach ($funding as $source) {
                    Log::info('Creating funding source', $source);

                    // Handle recipient_id - it could be an existing person ID or a new team member index
                    $recipientId = $source['recipient_id'];

                    // If it's a new team member (format: new_0, new_1, etc.)
                    if (str_starts_with($recipientId, 'new_')) {
                        $teamIndex = (int) str_replace('new_', '', $recipientId);
                        if (isset($team[$teamIndex])) {
                            // Find the person that was created for this team member
                            $teamMember = $team[$teamIndex];

                            // For new team members, we need to find the person that was just created
                            // We can do this by matching the email since that's unique
                            $person = People::where('email', $teamMember['email'])->first();
                            if ($person) {
                                $recipientId = $person->id;
                                Log::info('Found existing person for new team member', ['person_id' => $person->id, 'email' => $teamMember['email']]);
                            } else {
                                // This shouldn't happen, but if it does, skip this funding source
                                Log::warning('Could not find person for new team member', ['team_index' => $teamIndex, 'email' => $teamMember['email']]);

                                continue;
                            }
                        } else {
                            Log::warning('Invalid team member index for funding recipient', ['index' => $teamIndex]);

                            continue;
                        }
                    }

                    $fundingRecord = Fundings::create([
                        'source' => $source['source'],
                        'recipient_id' => $recipientId,
                        'amount' => $source['amount'],
                        'currency' => $source['currency'],
                        'reference' => $source['reference'],
                        'start_date' => $source['start_date'],
                        'end_date' => $source['end_date'],
                    ]);

                    // Attach funding to project
                    $project->fundings()->attach($fundingRecord->id);
                }
            }

            // Create documents (optional)
            if (! empty($documents)) {
                foreach ($documents as $index => $document) {
                    $filePath = null;
                    $fileName = null;
                    $mimeType = null;

                    if (isset($document['file_info']) && $document['file_info']) {
                        $fileInfo = $document['file_info'];

                        if (isset($fileInfo['temp_path']) && $fileInfo['temp_path']) {
                            // Move file from temp location to final location
                            $tempPath = $fileInfo['temp_path'];
                            $finalPath = str_replace('temp-documents/', 'documents/', $tempPath);

                            // Move the file to the final location
                            if (Storage::disk('local')->exists($tempPath)) {
                                Storage::disk('local')->move($tempPath, $finalPath);
                                $filePath = $finalPath;
                                $fileName = $fileInfo['original_name'];
                                $mimeType = $fileInfo['mime_type'];
                                Log::info('File moved from temp to final location', ['temp_path' => $tempPath, 'final_path' => $finalPath]);
                            } else {
                                Log::warning('Temp file not found', ['temp_path' => $tempPath]);
                            }
                        }
                    } else {
                        Log::info("Document {$index} has no file info - creating document without file");
                    }

                    $documentData = [
                        'title' => $document['title'],
                        'type' => $document['type'],
                        'description' => $document['description'] ?? null,
                        'document_date' => $document['document_date'],
                    ];

                    // Only add file-related fields if a file was uploaded
                    if ($filePath && $fileName && $mimeType) {
                        $documentData['file_path'] = $filePath;
                        $documentData['file_name'] = $fileName;
                        $documentData['mime_type'] = $mimeType;
                    } else {
                        // Set empty strings for required fields when no file is uploaded
                        $documentData['file_path'] = '';
                        $documentData['file_name'] = '';
                        $documentData['mime_type'] = '';
                    }

                    Log::info("Creating document record for document {$index}:", $documentData);
                    $project->documents()->create($documentData);
                    Log::info("Document {$index} created successfully");
                }
            }

            // Clear session data
            session()->forget(['project.general', 'project.team', 'project.funding', 'project.documents']);

            DB::commit();
            Log::info('Project creation completed successfully', ['project_id' => $project->id]);

            return redirect()->route('profile.projects')->with('success', 'Project created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            // Clean up temporary files
            $this->cleanupTempFiles($documents);

            Log::error('Project creation failed: '.$e->getMessage(), [
                'exception' => $e,
                'general' => $general,
                'team' => $team,
                'funding' => $funding,
                'documents' => $documents,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('projects.create', ['step' => 1])
                ->with('error', 'An error occurred while creating the project. Please try again.');
        }
    }

    private function generateNextProjectCode($existingCodes)
    {
        // Build a fast lookup set of existing valid codes
        $existingSet = [];
        foreach ($existingCodes as $code) {
            $code = strtoupper($code);
            if (preg_match('/^[A-Z][1-9][A-Z][1-9]$/', $code)) {
                $existingSet[$code] = true;
            }
        }

        // Start from the very first code in the sequence
        $letter1 = 'A';
        $number1 = 1;
        $letter2 = 'A';
        $number2 = 1;

        // Local function to advance to the next code in the required order:
        // A1A1 → A1A2 → ... → A1A9 → A1B1 → ... → A1Z9 → B1A1 → ... → Z9Z9 → wraps to A1A1
        $advance = function (string $l1, int $n1, string $l2, int $n2): array {
            if ($n2 < 9) {
                $n2++;
            } else {
                $n2 = 1;
                if ($l2 < 'Z') {
                    $l2 = chr(ord($l2) + 1);
                } else {
                    $l2 = 'A';
                    if ($l1 < 'Z') {
                        $l1 = chr(ord($l1) + 1);
                    } else {
                        $l1 = 'A';
                        if ($n1 < 9) {
                            $n1++;
                        } else {
                            // Full wrap-around after Z9Z9
                            $n1 = 1;
                        }
                    }
                }
            }

            return [$l1, $n1, $l2, $n2];
        };

        // Find the first available code in sequence, skipping codes that already exist
        $safetyCounter = 0;
        while (isset($existingSet[$letter1.$number1.$letter2.$number2])) {
            [$letter1, $number1, $letter2, $number2] = $advance($letter1, $number1, $letter2, $number2);
            $safetyCounter++;
            // Prevent potential infinite loop in pathological cases
            if ($safetyCounter > (26 * 9 * 26 * 9)) {
                // Fallback: extremely unlikely; return current composition
                break;
            }
        }

        return $letter1.$number1.$letter2.$number2;
    }

    public function show(Projects $projects)
    {
        //
    }

    public function review()
    {
        $general = session('project.general');
        $team = session('project.team');
        $funding = session('project.funding');
        $documents = session('project.documents');

        // Get next available project code for display
        $existingCodes = Projects::pluck('code')->toArray();
        $nextCode = $this->generateNextProjectCode($existingCodes);

        // Debug session data
        Log::info('Projects review method called', [
            'general_exists' => ! empty($general),
            'team_exists' => ! empty($team),
            'funding_exists' => ! empty($funding),
            'documents_exists' => ! empty($documents),
            'general_data' => $general,
            'team_data' => $team,
            'funding_data' => $funding,
            'documents_data' => $documents,
        ]);

        if (! $general || ! $team) {
            Log::warning('Missing required session data in review method', [
                'general' => $general,
                'team' => $team,
            ]);

            return redirect()->route('projects.create', ['step' => 1])
                ->with('error', 'Please complete all required steps before reviewing the project.');
        }

        return view('projects.review', [
            'general' => $general,
            'team' => $team,
            'funding' => $funding ?? [],
            'documents' => $documents ?? [],
            'nextCode' => $nextCode,
        ]);
    }

    public function edit(Projects $project, $section = 'general')
    {
        $this->authorize('update', $project);

        $sections = [
            'general' => 'General Information',
            'team' => 'Team Members',
            'funding' => 'Funding Details',
            'documents' => 'Project Documents',
        ];

        if (! array_key_exists($section, $sections)) {
            return redirect()->route('projects.edit', ['project' => $project->id, 'section' => 'general']);
        }

        // Load the project with its people and their pivot data
        $project->load(['people' => function ($query) {
            $query->withPivot('role', 'date_joined', 'permission', 'module_permissions');
        }, 'fundings.recipient']);

        // Get all people for the dropdown
        $people = People::with('users')->get();

        return view('projects.edit', [
            'project' => $project,
            'section' => $section,
            'sections' => $sections,
            'people' => $people,
            'modulePermissionOptions' => ProjectPermission::moduleOptions(),
        ]);
    }

    public function update(Request $request, Projects $project)
    {
        $this->authorize('update', $project);

        // Get section from request, fallback to 'general' if not provided
        $section = $request->input('section', 'general');

        try {
            DB::beginTransaction();

            switch ($section) {
                case 'team':
                    // Validate team members
                    $validator = Validator::make($request->all(), [
                        'team_members' => 'required|array|min:1',
                        'team_members.*.title' => 'required|string|max:255',
                        'team_members.*.first_name' => 'required|string|max:255',
                        'team_members.*.last_name' => 'required|string|max:255',
                        'team_members.*.email' => 'required|email|max:255',
                        'team_members.*.role' => 'required|string',
                        'team_members.*.permission' => 'required|string',
                        'team_members.*.module_permissions' => 'nullable|array',
                        'team_members.*.module_permissions.*' => 'string|in:'.implode(',', array_keys(ProjectPermission::moduleOptions())),
                        'team_members.*.date_joined' => 'required|date',
                        'team_members.*.person_id' => 'nullable|exists:people,id',
                    ]);

                    if ($validator->fails()) {
                        return back()->withErrors($validator)->withInput();
                    }

                    $originalPersonIds = $project->people()
                        ->pluck('people.id')
                        ->map(fn ($id) => (int) $id)
                        ->all();

                    // First, remove all existing team members
                    $project->people()->detach();

                    // Then add the new team members
                    foreach ($request->team_members as $member) {
                        $personId = null;

                        if (! empty($member['person_id'])) {
                            $personId = (int) $member['person_id'];
                        } else {
                            $existingPerson = People::where('email', $member['email'])->first();
                            if (! $existingPerson) {
                                $userForEmail = User::where('email', $member['email'])->first();
                                if ($userForEmail && $userForEmail->people) {
                                    $existingPerson = $userForEmail->people;
                                }
                            }

                            if ($existingPerson) {
                                $personId = (int) $existingPerson->id;
                            } else {
                                $newPerson = People::create([
                                    'title' => $member['title'],
                                    'first_name' => $member['first_name'],
                                    'last_name' => $member['last_name'],
                                    'email' => $member['email'],
                                ]);

                                $personId = (int) $newPerson->id;
                            }
                        }

                        $project->people()->attach($personId, [
                            'role' => $member['role'],
                            'permission' => $member['permission'],
                            'module_permissions' => json_encode($this->normalizeModulePermissionsForPermission((string) $member['permission'], (array) ($member['module_permissions'] ?? []))),
                            'date_joined' => $member['date_joined'],
                        ]);

                        if (! in_array($personId, $originalPersonIds, true)) {
                            $this->notifyProjectMemberAdded($project, People::find($personId), (string) $member['role']);
                        }
                    }

                    DB::commit();
                    session()->flash('success', 'Team members updated successfully!');

                    return back();

                case 'general':

                    $validator = Validator::make($request->all(), [
                        'type' => 'required|string|max:255',
                        'date_started' => 'required|date',
                        'date_end_intended' => 'nullable|date|after:date_started',
                        'title' => 'required|string|max:255',
                        'description' => 'nullable|string',
                        'ethics_ref' => 'nullable|string|max:255',
                        'alias_code' => 'nullable|string|max:255',
                    ]);

                    if ($validator->fails()) {
                        return back()->withErrors($validator)->withInput();
                    }

                    $description = $request->description !== null
                        ? trim((string) $request->description)
                        : null;

                    $project->update([
                        'type' => $request->type,
                        'date_started' => $request->date_started,
                        'date_end_intended' => $request->filled('date_end_intended') ? $request->date_end_intended : null,
                        'title' => $request->title,
                        'description' => $description,
                        // Keep legacy views that still read `notes` in sync.
                        'notes' => $description,
                        'ethics_ref' => $request->ethics_ref,
                        'alias_code' => $request->filled('alias_code') ? $request->alias_code : null,
                    ]);

                    DB::commit();

                    session()->flash('success', 'General Information updated successfully!');

                    return back();

                case 'funding':
                    // Handle funding section
                    if ($request->has('funding')) {
                        foreach ($request->funding as $index => $fundingData) {
                            if (isset($fundingData['id'])) {
                                // Update existing funding
                                $funding = Fundings::find($fundingData['id']);
                                if ($funding) {
                                    $funding->update([
                                        'source' => $fundingData['source'],
                                        'recipient_id' => $fundingData['recipient'],
                                        'amount' => $fundingData['amount'],
                                        'currency' => $fundingData['currency'],
                                        'reference' => $fundingData['reference'],
                                        'start_date' => $fundingData['start_date'],
                                        'end_date' => $fundingData['end_date'],
                                    ]);
                                }
                            } else {
                                // Create new funding
                                $newFunding = Fundings::create([
                                    'source' => $fundingData['source'],
                                    'recipient_id' => $fundingData['recipient'],
                                    'amount' => $fundingData['amount'],
                                    'currency' => $fundingData['currency'],
                                    'reference' => $fundingData['reference'],
                                    'start_date' => $fundingData['start_date'],
                                    'end_date' => $fundingData['end_date'],
                                ]);

                                // Attach the new funding to the project
                                $project->fundings()->attach($newFunding->id);
                            }
                        }
                    }

                    if ($request->has('removed_fundings')) {
                        $project->fundings()->detach($request->removed_fundings);
                    }

                    DB::commit();

                    return back()->with('success', 'Funding details updated successfully!');

                case 'documents':
                    // Handle documents section
                    if ($request->has('documents')) {
                        foreach ($request->documents as $index => $documentData) {
                            if (isset($documentData['id'])) {
                                // Update existing document
                                $document = Documents::find($documentData['id']);
                                if ($document) {
                                    $document->update([
                                        'title' => $documentData['title'],
                                        'type' => $documentData['type'],
                                        'description' => $documentData['description'],
                                        'document_date' => $documentData['document_date'],
                                    ]);

                                    // Handle file upload if a new file is provided
                                    if (isset($documentData['file']) && $documentData['file']) {
                                        // Delete old file if it exists
                                        if ($document->file_path && Storage::exists($document->file_path)) {
                                            Storage::delete($document->file_path);
                                        }

                                        // Store new file
                                        $path = $documentData['file']->store('documents', 'local');
                                        $document->update([
                                            'file_path' => $path,
                                            'file_name' => $documentData['file']->getClientOriginalName(),
                                            'mime_type' => $documentData['file']->getMimeType(),
                                        ]);
                                    }
                                }
                            } else {
                                // Create new document
                                if (isset($documentData['file']) && $documentData['file']) {
                                    $path = $documentData['file']->store('documents', 'local');

                                    Documents::create([
                                        'projects_id' => $project->id,
                                        'title' => $documentData['title'],
                                        'type' => $documentData['type'],
                                        'file_path' => $path,
                                        'file_name' => $documentData['file']->getClientOriginalName(),
                                        'mime_type' => $documentData['file']->getMimeType(),
                                        'description' => $documentData['description'],
                                        'document_date' => $documentData['document_date'],
                                    ]);
                                }
                            }
                        }
                    }

                    if ($request->has('removed_documents')) {
                        foreach ($request->removed_documents as $documentId) {
                            $document = Documents::find($documentId);
                            if ($document) {
                                if ($document->file_path) {
                                    Storage::delete($document->file_path);
                                }
                                $document->delete();
                            }
                        }
                    }

                    DB::commit();

                    return back()->with('success', 'Documents updated successfully!');

                default:
                    DB::rollBack();

                    return back()->with('error', 'Invalid section specified');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'An unexpected error occurred: '.$e->getMessage());

            return back()->withInput();
        }
    }

    public function destroy(Projects $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        try {
            DB::transaction(function () use ($project): void {
                $project->load('documents');

                foreach ($project->documents as $document) {
                    if ($document->file_path) {
                        Storage::delete($document->file_path);
                    }
                }

                Message::query()->where('projects_id', $project->id)->delete();
                EnvironmentSamples::query()->where('projects_id', $project->id)->delete();

                $project->delete();
            });

            if ((int) session('selected_project_id') === (int) $project->id) {
                session()->forget('selected_project_id');
            }

            return redirect()
                ->route('profile.projects')
                ->with('success', 'Project deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete project', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete project: '.$e->getMessage());
        }
    }

    public function detachFunding(Projects $project, Fundings $funding)
    {
        $this->authorize('update', $project);

        try {
            $project->fundings()->detach($funding->id);

            return back()->with('success', 'Funding source removed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to remove funding source: '.$e->getMessage());
        }
    }

    public function detachDocument(Projects $project, Documents $document)
    {
        $this->authorize('update', $project);

        try {
            // Delete the file from storage
            if ($document->file_path) {
                Storage::delete($document->file_path);
            }

            // Delete the document record
            $document->delete();

            return back()->with('success', 'Document removed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to remove document: '.$e->getMessage());
        }
    }

    /**
     * @param  array<int, mixed>  $modules
     * @return array<int, string>
     */
    private function normalizeModulePermissionsForPermission(string $permission, array $modules): array
    {
        if (in_array($permission, ['admin', 'editor'], true)) {
            return [];
        }

        $allowed = array_keys(ProjectPermission::moduleOptions());

        return array_values(array_unique(array_filter(
            array_map(static fn ($value) => is_string($value) ? trim($value) : null, $modules),
            static fn (?string $value) => $value !== null && in_array($value, $allowed, true)
        )));
    }

    private function notifyProjectMemberAdded(Projects $project, ?People $person, string $role): void
    {
        $targetUser = $person?->users;

        if (! $targetUser || $targetUser->id === Auth::id()) {
            return;
        }

        NotificationController::createForUser(
            $targetUser,
            'project_invitation',
            'Added to project team',
            'You were added to project "'.($project->title ?: $project->code).'" as '.$role.'.',
            route('profile.projects'),
            $project->id
        );
    }
}
