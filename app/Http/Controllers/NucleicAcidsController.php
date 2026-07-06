<?php

namespace App\Http\Controllers;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\Projects;
use App\Models\Protocols;
use App\Models\Tubes;
use App\Services\NucleicAcidsService;
use App\Support\ProjectPermission;
use App\Support\SubProjectFlag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NucleicAcidsController extends Controller
{
    protected $service;

    public function __construct(NucleicAcidsService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        $data = $this->service->dataForCreate();
        $projectId = (int) session('selected_project_id');
        $user = Auth::user();

        return view('samples.nucleic_acids.create', array_merge($data, [
            'selected_project_id' => $projectId,
            'can_assign_registrar' => $user ? ProjectPermission::canAssignRegistrar($user, $projectId) : false,
            'locked_registrar_people_id' => $user ? ProjectPermission::currentRegistrarPeopleId($user) : null,
            'sub_project_options' => SubProjectFlag::optionsForUser($user, $projectId),
        ]));
    }

    public function store()
    {
        $projectId = (int) session('selected_project_id');
        if ($projectId <= 0) {
            session()->flash('error', 'No project selected. Please select a project and try again.');

            return back()->withInput();
        }
        $project = Projects::findOrFail($projectId);
        $project_code = $project->code;

        $rules = [
            'human_tube_id' => 'required_if:model,Human samples',
            'animal_tube_id' => 'required_if:model,Animal samples',
            'environment_tube_id' => 'required_if:model,Environmental samples',
            'parasite_tube_id' => 'required_if:model,Parasite samples',
            'experiment_id' => 'required_if:model,Experiments',
            'culture_tube_id' => 'required_if:model,Cultures',
            'pool_tube_id' => 'required_if:model,Pools',
            'type' => 'string|max:100|required',
            'protocol' => 'string|max:100|required',
            'elution' => 'numeric|integer:1,1000|required',
            'date' => 'required|date|before_or_equal:today',
            'nucleic_lab' => 'required|string|max:100',
            'extractor' => 'required|exists:people,id',
            'is_historical' => 'required|in:0,1',
            'alias_code_assignments' => 'required_if:is_historical,1|array',
            'alias_code_assignments.*' => 'nullable|string|max:255',
            'sub_project_id' => 'nullable|integer|exists:sub_projects,id',
            'project_id_snapshot' => 'required|integer|exists:projects,id',
        ];

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {
            $submittedProjectSnapshot = (int) request('project_id_snapshot');
            if ($submittedProjectSnapshot !== $projectId) {
                session()->flash('error', 'The selected project changed while this form was open. Please reload the page and submit again.');

                return back()->withInput();
            }

            $model = request('model');
            $tubes_id = [];
            $isHistorical = request('is_historical');
            $aliasCodeAssignments = request('alias_code_assignments', []);
            $selectedSubProjectId = request('sub_project_id') ? (int) request('sub_project_id') : null;
            if (! SubProjectFlag::isSelectableByUser(Auth::user(), (int) $projectId, $selectedSubProjectId)) {
                session()->flash('error', 'Selected sub-project is not allowed for your user.');

                return back()->withInput();
            }

            // Get the appropriate tube IDs based on the model
            switch ($model) {
                case 'Human samples':
                    $tubes_id = request('human_tube_id', []);
                    $content_type = HumanSamples::class;
                    break;
                case 'Animal samples':
                    $tubes_id = request('animal_tube_id', []);
                    $content_type = AnimalSamples::class;
                    break;
                case 'Environmental samples':
                    $tubes_id = request('environment_tube_id', []);
                    $content_type = EnvironmentSamples::class;
                    break;
                case 'Parasite samples':
                    $tubes_id = request('parasite_tube_id', []);
                    $content_type = ParasiteSamples::class;
                    break;
                case 'Experiments':
                    $tubes_id = request('experiment_id', []);
                    $content_type = Experiments::class;
                    break;
                case 'Cultures':
                    $tubes_id = request('culture_tube_id', []);
                    $content_type = Cultures::class;
                    break;
                case 'Pools':
                    $tubes_id = request('pool_tube_id', []);
                    $content_type = Pools::class;
                    break;
                default:
                    throw new \Exception('Invalid sample type selected');
            }

            $protocols_id = $this->service->check_or_create(
                Protocols::class,
                ['name' => request('protocol')]
            );

            $nucleic_lab_id = $this->service->check_or_create(
                Laboratories::class,
                ['name' => request('nucleic_lab')]
            );
            $extractorPeopleId = $this->resolveRegistrarPeopleId('extractor');

            $tubeIndex = 0;
            foreach ($tubes_id as $tube_id) {

                if ($content_type === Experiments::class) {
                    // For experiments, we need to ensure the tube ID is valid
                    $nucleic_content_id = $tube_id;
                } else {
                    // Get the tube content ID
                    $nucleic_content_id = Tubes::whereHas('tubes_content', function ($query) use ($content_type) {
                        $query->where('tubes_content_type', $content_type);
                    })
                        ->where('id', $tube_id)
                        ->first();

                    if (! $nucleic_content_id) {
                        throw new \Exception("Tube content not found for ID: {$tube_id}");
                    }

                    $nucleic_content_id = $nucleic_content_id->tubes_content_id;

                }

                // Generate unique nucleic acid code
                $existingNaCodes = NucleicAcids::where('projects_id', $projectId)
                    ->where('code', 'like', $project_code.'-NA-%')
                    ->pluck('code');

                $usedNumbers = $existingNaCodes->map(function ($code) {
                    preg_match('/-NA-(\d+)$/', $code, $matches);

                    return isset($matches[1]) ? (int) $matches[1] : null;
                })->filter()->sort()->values();

                $newSerial = 1;
                foreach ($usedNumbers as $num) {
                    if ($num != $newSerial) {
                        break;
                    }
                    $newSerial++;
                }

                $na_code = $project_code.'-NA-'.$newSerial;

                // Create nucleic acid record
                $nucleicAcid = NucleicAcids::create([
                    'code' => $na_code,
                    'type' => request('type'),
                    'nucleic_content_type' => $content_type,
                    'nucleic_content_id' => $nucleic_content_id,
                    'protocols_id' => $protocols_id,
                    'date_extracted' => request('date'),
                    'volume' => request('elution'),
                    'laboratories_id' => $nucleic_lab_id,
                    'people_id' => $extractorPeopleId,
                    'projects_id' => $projectId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                SubProjectFlag::assign($nucleicAcid, $selectedSubProjectId);

                // Generate unique tube code
                $existingTubeCodes = Tubes::where('projects_id', $projectId)
                    ->where('code', 'like', $na_code.'-%')
                    ->pluck('code');

                $usedNumbers = $existingTubeCodes->map(function ($code) {
                    preg_match('/-(\d+)$/', $code, $matches);

                    return isset($matches[1]) ? (int) $matches[1] : null;
                })->filter()->sort()->values();

                $newSerial = 1;
                foreach ($usedNumbers as $num) {
                    if ($num != $newSerial) {
                        break;
                    }
                    $newSerial++;
                }

                $tube_code = $na_code.'-'.$newSerial;

                // Get alias code if this is a historical extraction
                $aliasCode = null;
                if ($isHistorical && isset($aliasCodeAssignments[$tubeIndex])) {
                    $aliasCode = $aliasCodeAssignments[$tubeIndex];
                }

                // Create tube record
                Tubes::create([
                    'code' => $tube_code,
                    'alias_code' => $aliasCode,
                    'tubes_content_id' => $nucleicAcid->id,
                    'tubes_content_type' => NucleicAcids::class,
                    'tube_type' => '1.5ml/2ml tube',
                    'purpose' => 'for nucleic acid storage',
                    'preservant' => request('solution'),
                    'date_processed' => request('date'),
                    'projects_id' => $projectId,
                ]);

                $tubeIndex++;
            }

            session()->flash('success', 'Nucleic acid extracted successfully!');

            $count = count($tubes_id);
            $user = Auth::user();

            NotificationController::create(
                'nucleic_acids_created',
                'New Nucleic Acid',
                $user->people->first_name.' extracted '.$count.' nucleic acid'.($count > 1 ? 's.' : '.'),
                '/samples/nucleic/list',
                $projectId
            );

            return back();
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());

            return back()->withInput();
        }
    }

    private function resolveRegistrarPeopleId(string $requestKey): ?int
    {
        $user = Auth::user();
        if (! $user || ! $user->people) {
            return request($requestKey) ? (int) request($requestKey) : null;
        }

        $projectId = (int) session('selected_project_id');
        if (! ProjectPermission::canAssignRegistrar($user, $projectId)) {
            return (int) $user->people->id;
        }

        return request($requestKey) ? (int) request($requestKey) : (int) $user->people->id;
    }
}
