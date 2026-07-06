<?php

namespace App\Http\Controllers;

use App\Models\Laboratories;
use App\Models\PoolContents;
use App\Models\Pools;
use App\Models\Projects;
use App\Models\Tubes;
use App\Services\ExperimentsService;
use App\Support\ProjectPermission;
use App\Support\SubProjectFlag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PoolsController extends Controller
{
    protected $service;

    public function __construct(ExperimentsService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        $experiments_service = app(ExperimentsService::class);

        $experiments_data = $experiments_service->dataForPoolsCreate();

        $selected_human_tubes = $this->selectedTubesFromOldInput('human_tube_id');
        $selected_animal_tubes = $this->selectedTubesFromOldInput('animal_tube_id');
        $selected_environment_tubes = $this->selectedTubesFromOldInput('environment_tube_id');
        $selected_parasite_tubes = $this->selectedTubesFromOldInput('parasite_tube_id');
        $selected_nucleic_tubes = $this->selectedTubesFromOldInput('nucleic_tube_id');
        $selected_culture_tubes = $this->selectedTubesFromOldInput('culture_tube_id');

        $data = [
            'selected_human_tubes' => $selected_human_tubes,
            'selected_animal_tubes' => $selected_animal_tubes,
            'selected_environment_tubes' => $selected_environment_tubes,
            'selected_parasite_tubes' => $selected_parasite_tubes,
            'selected_nucleic_tubes' => $selected_nucleic_tubes,
            'selected_culture_tubes' => $selected_culture_tubes,
            'laboratories_by_country' => $experiments_data['laboratories_by_country'],
            'people' => $experiments_data['people'],
        ];
        $user = Auth::user();
        $data['can_assign_registrar'] = $user ? ProjectPermission::canAssignRegistrar($user, (int) session('selected_project_id')) : false;
        $data['locked_registrar_people_id'] = $user ? ProjectPermission::currentRegistrarPeopleId($user) : null;
        $data['sub_project_options'] = SubProjectFlag::optionsForUser($user, (int) session('selected_project_id'));

        return view('samples.pools.create', $data);
    }

    private function selectedTubesFromOldInput(string $key): Collection
    {
        $ids = array_values(array_filter((array) old($key, [])));

        if (! $ids) {
            return collect();
        }

        return Tubes::query()
            ->whereIn('id', $ids)
            ->get(['id', 'code']);
    }

    public function store()
    {
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);
        $project_code = $project->code;

        $rules = [
            'human_tube_id' => 'required_if:model,Human samples',
            'animal_tube_id' => 'required_if:model,Animal samples',
            'environment_tube_id' => 'required_if:model,Environmental samples',
            'parasite_tube_id' => 'required_if:model,Parasite samples',
            'nucleic_tube_id' => 'required_if:model,Nucleic acids',
            'culture_tube_id' => 'required_if:model,Cultures',
            'date_pooled' => 'required|date|before_or_equal:today',
            'lab' => 'string|max:200|required',
            'pooler' => 'required|exists:people,id',
            'sub_project_id' => 'nullable|integer|exists:sub_projects,id',
        ];

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {

            $lab_id = $this->service->check_or_create(
                Laboratories::class,
                ['name' => request('lab')]
            );
            $poolerPeopleId = $this->resolveRegistrarPeopleId('pooler');
            $selectedSubProjectId = request('sub_project_id') ? (int) request('sub_project_id') : null;
            if (! SubProjectFlag::isSelectableByUser(Auth::user(), (int) $projectId, $selectedSubProjectId)) {
                session()->flash('error', 'Selected sub-project is not allowed for your user.');

                return back()->withInput();
            }

            $model = request('model');

            if ($model === 'Human samples') {
                $tubes_id = request('human_tube_id', []);
            } elseif ($model === 'Animal samples') {
                $tubes_id = request('animal_tube_id', []);
            } elseif ($model === 'Environmental samples') {
                $tubes_id = request('environment_tube_id', []);
            } elseif ($model === 'Parasite samples') {
                $tubes_id = request('parasite_tube_id', []);
            } elseif ($model === 'Nucleic acids') {
                $tubes_id = request('nucleic_tube_id', []);
            } elseif ($model === 'Cultures') {
                $tubes_id = request('culture_tube_id', []);
            }

            $project_code = Projects::where('id', $projectId)->first()->code;

            $existingPoolCodes = Pools::where('projects_id', $projectId)
                ->where('code', 'like', $project_code.'-PO-%')
                ->pluck('code');

            $usedNumbers = $existingPoolCodes->map(function ($code) {
                preg_match('/-PO-(\d+)$/', $code, $matches);

                return isset($matches[1]) ? (int) $matches[1] : null;
            })->filter()->sort()->values();

            $newSerial = 1;
            foreach ($usedNumbers as $num) {
                if ($num != $newSerial) {
                    break;
                }
                $newSerial++;
            }

            $pool_code = $project_code.'-PO-'.$newSerial;

            // Collect unique samples from tubes
            $unique_samples = [];
            foreach ($tubes_id as $tube_id) {
                $tube = Tubes::find($tube_id);
                if (! $tube) {
                    continue;
                }
                $sample_id = $tube->tubes_content ? $tube->tubes_content->id : null;
                $sample_type = $tube->tubes_content_type;
                if ($sample_id && $sample_type) {
                    $key = $sample_type.':'.$sample_id;
                    $unique_samples[$key] = [
                        'samples_type' => $sample_type,
                        'samples_id' => $sample_id,
                    ];
                }
            }

            [$pool, $tube] = DB::transaction(function () use ($pool_code, $unique_samples, $lab_id, $projectId, $poolerPeopleId) {
                $pool = Pools::create([
                    'code' => $pool_code,
                    'nr_pooled' => count($unique_samples),
                    'date_pooled' => request('date_pooled'),
                    'people_id' => $poolerPeopleId,
                    'laboratories_id' => $lab_id,
                    'projects_id' => $projectId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Create PoolContents only for unique samples
                foreach ($unique_samples as $sample) {
                    PoolContents::create([
                        'samples_type' => $sample['samples_type'],
                        'samples_id' => $sample['samples_id'],
                        'pools_id' => $pool->id,
                    ]);
                }

                // Generate unique tube code
                $existingTubeCodes = Tubes::where('projects_id', $projectId)
                    ->where('code', 'like', $pool_code.'-%')
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

                $tube_code = $pool_code.'-'.$newSerial;

                $tube = Tubes::create([
                    'code' => $tube_code,
                    'tubes_content_type' => Pools::class,
                    'tubes_content_id' => $pool->id,
                    'tube_type' => '1.5ml/2ml tube',
                    'purpose' => 'for pooling',
                    'date_processed' => request('date_pooled'),
                    'projects_id' => $projectId,
                ]);

                return [$pool, $tube];
            });
            SubProjectFlag::assign($pool, $selectedSubProjectId);

            session()->flash('success', 'Pool registered successfully!');

            $user = Auth::user();

            NotificationController::create(
                'pool_created',
                'New Pool of Samples',
                $user->people->first_name.' registered a new pool with '.count($tubes_id).' samples.',
                '/samples/pools/list',
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
