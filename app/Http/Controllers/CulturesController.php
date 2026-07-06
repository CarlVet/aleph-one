<?php

namespace App\Http\Controllers;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\Projects;
use App\Models\Tubes;
use App\Services\CulturesService;
use App\Support\ProjectPermission;
use App\Support\SubProjectFlag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CulturesController extends Controller
{
    protected $service;

    public function __construct(CulturesService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);
        $project_code = $project->code;

        // Get all existing culture codes for this project
        $existingCuCodes = Cultures::where('projects_id', $projectId)
            ->where('code', 'like', $project_code.'-CU-%')
            ->pluck('code');

        $usedNumbers = $existingCuCodes->map(function ($code) {
            preg_match('/-CU-(\d+)$/', $code, $matches);

            return isset($matches[1]) ? (int) $matches[1] : null;
        })->filter()->sort()->values();

        // Generate a list of available codes
        $availableCodes = [];
        $maxNumber = $usedNumbers->max() ?? 0;

        // Generate codes from 1 to max + 200 to ensure enough availability
        for ($i = 1; $i <= $maxNumber + 200; $i++) {
            if (! $usedNumbers->contains($i)) {
                $availableCodes[] = $project_code.'-CU-'.$i;
            }
        }

        // If no codes were generated, add some initial codes
        if (empty($availableCodes)) {
            for ($i = 1; $i <= 100; $i++) {
                $availableCodes[] = $project_code.'-CU-'.$i;
            }
        }

        $viewData = $this->service->dataForCreate();
        $viewData['available_codes'] = $availableCodes;
        $viewData['project_code'] = $project_code;
        $user = Auth::user();
        $viewData['can_assign_registrar'] = $user ? ProjectPermission::canAssignRegistrar($user, (int) $projectId) : false;
        $viewData['locked_registrar_people_id'] = $user ? ProjectPermission::currentRegistrarPeopleId($user) : null;
        $viewData['sub_project_options'] = SubProjectFlag::optionsForUser($user, (int) $projectId);

        return view('samples.cultures.create', $viewData);
    }

    public function store()
    {
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);
        $project_code = $project->code;

        $rules = [
            'culture_type' => 'string|max:200|required',
            'culture_medium' => 'string|max:200|required',
            'culture_athmosphere' => 'string|max:200|required',
            'date' => 'required|date|before_or_equal:today',
            'lab' => 'string|max:100|required',
            'scientist' => 'required|exists:people,id',
            'culture_codes' => 'array',
            'culture_codes.*' => 'required|string|unique:cultures,code',
            'culture_alias_codes' => 'array',
            'culture_alias_codes.*' => 'nullable|string|max:255',
            'culture_id' => 'required_if:culture_step,No|array',
            'culture_id.*' => 'required|exists:cultures,id',
            'incubation_temp' => 'required|integer|min:0|max:100',
            'sub_project_id' => 'nullable|integer|exists:sub_projects,id',
        ];

        // Add conditional validation based on culture_step and model
        if (request('culture_step') === 'Yes') {
            $model = request('model');

            if ($model === 'Animal samples') {
                $rules['animal_tube_id'] = 'required|array';
                $rules['animal_tube_id.*'] = 'required|exists:tubes,id';
            } elseif ($model === 'Parasite samples') {
                $rules['parasite_tube_id'] = 'required|array';
                $rules['parasite_tube_id.*'] = 'required|exists:tubes,id';
            } elseif ($model === 'Human samples') {
                $rules['human_tube_id'] = 'required|array';
                $rules['human_tube_id.*'] = 'required|exists:tubes,id';
            } elseif ($model === 'Environment samples') {
                $rules['environment_tube_id'] = 'required|array';
                $rules['environment_tube_id.*'] = 'required|exists:tubes,id';
            } elseif ($model === 'Pools') {
                $rules['pool_tube_id'] = 'required|array';
                $rules['pool_tube_id.*'] = 'required|exists:tubes,id';
            }
        }

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {
            $scientistPeopleId = $this->resolveRegistrarPeopleId('scientist');
            $selectedSubProjectId = request('sub_project_id') ? (int) request('sub_project_id') : null;
            if (! SubProjectFlag::isSelectableByUser(Auth::user(), (int) $projectId, $selectedSubProjectId)) {
                session()->flash('error', 'Selected sub-project is not allowed for your user.');

                return back()->withInput();
            }

            if (request('culture_step') === 'Yes') {

                $model = request('model');

                if ($model === 'Animal samples') {
                    $tubes_id = request('animal_tube_id', []); // Default to empty array
                } elseif ($model === 'Parasite samples') {
                    $tubes_id = request('parasite_tube_id', []);
                } elseif ($model === 'Human samples') {
                    $tubes_id = request('human_tube_id', []);
                } elseif ($model === 'Environment samples') {
                    $tubes_id = request('environment_tube_id', []);
                } elseif ($model === 'Pools') {
                    $tubes_id = request('pool_tube_id', []);
                }

                foreach ($tubes_id as $tube_id) {

                    $current_cultures_content_id = null;
                    $current_model = null;

                    if ($model === 'Animal samples') {
                        $current_cultures_content_id = Tubes::whereHas('tubes_content', function ($query) {
                            $query->where('tubes_content_type', AnimalSamples::class);
                        })
                            ->where('id', $tube_id)
                            ->first();

                        if ($current_cultures_content_id) {
                            $current_cultures_content_id = $current_cultures_content_id->tubes_content_id;
                        }

                        $current_model = AnimalSamples::class;
                    } elseif ($model === 'Parasite samples') {
                        $current_cultures_content_id = Tubes::whereHas('tubes_content', function ($query) {
                            $query->where('tubes_content_type', ParasiteSamples::class);
                        })
                            ->where('id', $tube_id)
                            ->first();

                        if ($current_cultures_content_id) {
                            $current_cultures_content_id = $current_cultures_content_id->tubes_content_id;
                        }

                        $current_model = ParasiteSamples::class;
                    } elseif ($model === 'Human samples') {
                        $current_cultures_content_id = Tubes::whereHas('tubes_content', function ($query) {
                            $query->where('tubes_content_type', HumanSamples::class);
                        })
                            ->where('id', $tube_id)
                            ->first();

                        if ($current_cultures_content_id) {
                            $current_cultures_content_id = $current_cultures_content_id->tubes_content_id;
                        }

                        $current_model = HumanSamples::class;
                    } elseif ($model === 'Environment samples') {
                        $current_cultures_content_id = Tubes::whereHas('tubes_content', function ($query) {
                            $query->where('tubes_content_type', EnvironmentSamples::class);
                        })
                            ->where('id', $tube_id)
                            ->first();

                        if ($current_cultures_content_id) {
                            $current_cultures_content_id = $current_cultures_content_id->tubes_content_id;
                        }

                        $current_model = EnvironmentSamples::class;
                    } elseif ($model === 'Pools') {
                        $current_cultures_content_id = Tubes::whereHas('tubes_content', function ($query) {
                            $query->where('tubes_content_type', Pools::class);
                        })
                            ->where('id', $tube_id)
                            ->first();

                        if ($current_cultures_content_id) {
                            $current_cultures_content_id = $current_cultures_content_id->tubes_content_id;
                        }

                        $current_model = Pools::class;
                    }

                    $lab_id = $this->service->check_or_create(
                        Laboratories::class,
                        ['name' => request('lab')]
                    );

                    // Get the culture code for this tube
                    $culture_code = request('culture_codes')[$tube_id] ?? null;
                    if (! $culture_code) {
                        continue; // Skip if no culture code provided
                    }

                    $culture = Cultures::create([
                        'code' => $culture_code,
                        'alias_code' => filled(request('culture_alias_codes')[$tube_id] ?? null)
                            ? trim((string) request('culture_alias_codes')[$tube_id])
                            : null,
                        'parent_id' => null,
                        'cultures_content_type' => $current_model,
                        'cultures_content_id' => $current_cultures_content_id,
                        'step' => 1,
                        'date_cultured' => request('date'),
                        'medium' => request('culture_medium'),
                        'type' => request('culture_type'),
                        'athmosphere' => request('culture_athmosphere'),
                        'incubation_temp' => request('incubation_temp'),
                        'people_id' => $scientistPeopleId,
                        'laboratories_id' => $lab_id,
                        'projects_id' => $projectId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    SubProjectFlag::assign($culture, $selectedSubProjectId);
                }
            } else {

                $cultures_id = request('culture_id');

                foreach ($cultures_id as $culture_id) {

                    $current_cultures_content_id = null;
                    $current_model = null;

                    $lab_id = $this->service->check_or_create(
                        Laboratories::class,
                        ['name' => request('lab')]
                    );

                    // Get the culture code for this tube
                    $culture_code = request('culture_codes')[$culture_id] ?? null;
                    if (! $culture_code) {
                        continue; // Skip if no culture code provided
                    }

                    $parent_culture = Cultures::where('id', $culture_id)->first();
                    if (! $parent_culture) {
                        continue; // Skip if parent culture not found
                    }

                    $culture = Cultures::create([
                        'code' => $culture_code,
                        'alias_code' => filled(request('culture_alias_codes')[$culture_id] ?? null)
                            ? trim((string) request('culture_alias_codes')[$culture_id])
                            : null,
                        'parent_id' => $culture_id,
                        'cultures_content_type' => $parent_culture->cultures_content_type,
                        'cultures_content_id' => $parent_culture->cultures_content_id,
                        'step' => $parent_culture->step + 1,
                        'date_cultured' => request('date'),
                        'medium' => request('culture_medium'),
                        'type' => request('culture_type'),
                        'athmosphere' => request('culture_athmosphere'),
                        'incubation_temp' => request('incubation_temp'),
                        'people_id' => $scientistPeopleId,
                        'laboratories_id' => $lab_id,
                        'projects_id' => $projectId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    SubProjectFlag::assign($culture, $selectedSubProjectId);
                }

            }

            session()->flash('success', 'Culture registered successfully!');

            if (request('culture_step') === 'Yes') {
                $count = count($tubes_id);
            } else {
                $count = count($cultures_id);
            }

            $user = Auth::user();

            NotificationController::create(
                'culture_created',
                'New Cultures',
                $user->people->first_name.' registered '.$count.' culture'.($count > 1 ? 's.' : '.'),
                '/samples/cultures/list',
                $projectId
            );

            return back();
        } catch (\Exception $e) {
            // Handle any exceptions during registration
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
