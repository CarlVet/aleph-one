<?php

namespace App\Http\Controllers;

use App\Models\EnvironmentSamples;
use App\Models\EnvironmentSampleTypes;
use App\Models\Locations;
use App\Models\Projects;
use App\Models\SamplingSites;
use App\Services\EnvironmentSamplesService;
use App\Support\ProjectPermission;
use App\Support\SubProjectFlag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EnvironmentSamplesController extends Controller
{
    protected $service;

    public function __construct(EnvironmentSamplesService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        $projectId = (int) session('selected_project_id');
        $user = Auth::user();

        return view('samples.environment.create', array_merge($this->service->assign(), [
            'can_assign_registrar' => $user ? ProjectPermission::canAssignRegistrar($user, $projectId) : false,
            'locked_registrar_people_id' => $user ? ProjectPermission::currentRegistrarPeopleId($user) : null,
            'sub_project_options' => SubProjectFlag::optionsForUser($user, $projectId),
        ]));
    }

    public function store()
    {
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);
        $project_code = $project->code;

        $rules = [
            'environment_sample_type' => 'required',
            'date' => 'required|date|before_or_equal:today',
            'sampling_site' => 'required|string',
            'area' => 'nullable|string|max:50|min:2',
            'latitude' => 'nullable|numeric|decimal:2,8',
            'longitude' => 'nullable|numeric|decimal:2,8',
            'location' => 'required|string',
            'field_labels_by_type' => 'nullable|array',
            'field_labels_by_type.*' => 'nullable|string|max:150',
            'sub_project_id' => 'nullable|integer|exists:sub_projects,id',
        ];

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {
            $collectorPeopleId = $this->resolveRegistrarPeopleId('collector');
            $selectedSubProjectId = request('sub_project_id') ? (int) request('sub_project_id') : null;
            if (! SubProjectFlag::isSelectableByUser(Auth::user(), (int) $projectId, $selectedSubProjectId)) {
                session()->flash('error', 'Selected sub-project is not allowed for your user.');

                return back()->withInput();
            }
            if (request()->hasFile('photo')) {
                $photoPath = request()->file('photo')->store('environment_samples', 'local');
            }

            $sampling_site_id = $this->service->check_or_create(
                SamplingSites::class,
                ['name' => request('sampling_site')]
            );

            $locations_id = $this->service->check_or_create(
                Locations::class,
                ['name' => request('location')]
            );

            foreach (request('environment_sample_type') as $sample_type) {
                $existingEsCodes = EnvironmentSamples::where('projects_id', $projectId)
                    ->where('code', 'like', $project_code.'-ES-%')
                    ->pluck('code');

                $usedNumbers = $existingEsCodes->map(function ($code) {
                    preg_match('/-ES-(\d+)$/', $code, $matches);

                    return isset($matches[1]) ? (int) $matches[1] : null;
                })->filter()->sort()->values();

                $newSerial = 1;
                foreach ($usedNumbers as $num) {
                    if ($num != $newSerial) {
                        break;
                    }
                    $newSerial++;
                }

                $es_code = $project_code.'-ES-'.$newSerial;
                $fieldLabelByType = (array) request('field_labels_by_type', []);
                $fieldLabel = isset($fieldLabelByType[$sample_type]) ? trim((string) $fieldLabelByType[$sample_type]) : '';

                $environment_sample_types_id = $this->service->check_or_create(
                    EnvironmentSampleTypes::class,
                    ['name' => $sample_type]
                );

                $environmentSample = EnvironmentSamples::create([
                    'code' => $es_code,
                    'field_label' => $fieldLabel !== '' ? $fieldLabel : null,
                    'environment_sample_types_id' => $environment_sample_types_id,
                    'date_collected' => request('date'),
                    'people_id' => $collectorPeopleId,
                    'sampling_sites_id' => $sampling_site_id,
                    'locations_id' => $locations_id,
                    'area' => request('area'),
                    'latitude' => request('latitude'),
                    'longitude' => request('longitude'),
                    'projects_id' => $projectId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                SubProjectFlag::assign($environmentSample, $selectedSubProjectId);
            }

            $count = count(request('environment_sample_type'));

            // Flash success message to the session
            session()->flash('success', 'Environment sample registered successfully!');

            // Get the authenticated user
            $user = Auth::user();

            // Create notification
            NotificationController::create(
                'environment_sample_created',
                'New Environment Samples',
                $user->people->first_name.' registered '.$count.' environment sample'.($count > 1 ? 's.' : '.'),
                '/samples/environment/list',
                $projectId  // Use dynamic project ID
            );

            return back();
        } catch (\Exception $e) {
            session()->flash('error', 'An unexpected error occurred: '.$e->getMessage());

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
