<?php

namespace App\Http\Controllers;

use App\Models\Humans;
use App\Models\HumanSamples;
use App\Models\Projects;
use App\Models\SampleTypes;
use App\Models\SamplingSites;
use App\Services\HumanSamplesService;
use App\Support\ProjectPermission;
use App\Support\SubProjectFlag;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HumanSamplesController extends Controller
{
    protected $service;

    public function __construct(HumanSamplesService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        $data = $this->service->dataForCreate();
        $projectId = (int) session('selected_project_id');
        $user = Auth::user();

        return view('samples.humans.create', array_merge($data, [
            'selected_humans' => $this->selectedHumansFromOldInput(),
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
            'humans_id' => 'required|array',
            'humans_id.*' => 'required|exists:humans,id',
            'date' => 'required|date|before_or_equal:today',
            'human_site' => 'required|string',
            'human_area' => 'nullable|string|max:200',
            'human_latitude' => 'nullable|numeric|between:-90,90',
            'human_longitude' => 'nullable|numeric|between:-180,180',
            'sampling_purpose' => 'required|string',
            'scientist' => 'required|exists:people,id',
            'human_sample_type' => 'required|array',
            'human_sample_type.*' => 'required|string',
            'human_location' => 'required|exists:locations,id',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,tif,tiff,pdf|max:51200', // 50MB max
            'new_sample_types' => 'array',
            'new_sample_types.*.name' => 'required|string|max:255',
            'new_sample_types.*.category' => 'required|in:host_derived,non_host_derived',
            'sub_project_id' => 'nullable|integer|exists:sub_projects,id',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {
            /** @var array<int|string, array{name?:string, category?:string}> $newSampleTypes */
            $newSampleTypes = (array) $request->input('new_sample_types', []);
            $newSampleTypeCategoriesByName = collect($newSampleTypes)
                ->mapWithKeys(function (array $row): array {
                    $name = trim((string) ($row['name'] ?? ''));
                    $category = (string) ($row['category'] ?? '');

                    return $name !== '' ? [$name => $category] : [];
                })
                ->filter(fn (string $category) => in_array($category, ['host_derived', 'non_host_derived'], true))
                ->all();
            $scientistPeopleId = $this->resolveRegistrarPeopleId('scientist');
            $selectedSubProjectId = $request->filled('sub_project_id') ? (int) $request->input('sub_project_id') : null;
            if (! SubProjectFlag::isSelectableByUser(Auth::user(), (int) $projectId, $selectedSubProjectId)) {
                session()->flash('error', 'Selected sub-project is not allowed for your user.');

                return back()->withInput();
            }

            // Create or get the sampling site place
            $site_id = $this->service->check_or_create(
                SamplingSites::class,
                ['name' => $request->human_site],
                [
                    'country' => 'South Africa',
                    'type' => 'Sampling site',
                ]
            );

            foreach ($request->humans_id as $human_id) {

                foreach ($request->human_sample_type as $sample_type_name) {
                    $sample_type_id = $this->service->check_or_create(
                        SampleTypes::class,
                        ['name' => $sample_type_name],
                        isset($newSampleTypeCategoriesByName[$sample_type_name])
                            ? ['category' => $newSampleTypeCategoriesByName[$sample_type_name]]
                            : []
                    );

                    $project_code = Projects::where('id', $projectId)->first()->code;

                    $existingCodes = HumanSamples::where('code', 'like', $project_code.'-HS-%')
                        ->pluck('code');

                    $usedNumbers = $existingCodes->map(function ($code) {
                        preg_match('/-HS-(\d+)$/', $code, $matches);

                        return isset($matches[1]) ? (int) $matches[1] : null;
                    })->filter()->sort()->values();

                    $newSerial = 1;
                    foreach ($usedNumbers as $num) {
                        if ($num != $newSerial) {
                            break;
                        }
                        $newSerial++;
                    }

                    $sample_code = $project_code.'-HS-'.$newSerial;

                    $humanSample = HumanSamples::create([
                        'code' => $sample_code,
                        'humans_id' => $human_id,
                        'sample_types_id' => $sample_type_id,
                        'date_collected' => $request->date,
                        'people_id' => $scientistPeopleId,
                        'sampling_sites_id' => $site_id,
                        'area' => $request->human_area,
                        'latitude' => $request->human_latitude,
                        'longitude' => $request->human_longitude,
                        'sample_purpose' => $request->sampling_purpose,
                        'locations_id' => request('human_location'),
                        'storage_state' => $request->storage_state, // Default storage state
                        'processed' => false,
                        'projects_id' => $projectId,
                    ]);
                    SubProjectFlag::assign($humanSample, $selectedSubProjectId);
                }
            }

            $count = count($request->humans_id) * count($request->human_sample_type);
            session()->flash('success', 'Human samples registered successfully!');

            NotificationController::create(
                'human_sample_created',
                'New Human Samples',
                Auth::user()->people->first_name.' registered '.$count.' human sample'.($count > 1 ? 's.' : '.'),
                '/samples/humans/list',
                $projectId
            );

            return back();
        } catch (\Exception $e) {
            session()->flash('error', 'An unexpected error occurred: '.$e->getMessage());

            return back()->withInput();
        }
    }

    private function selectedHumansFromOldInput(): Collection
    {
        $selectedIds = collect(old('humans_id', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($selectedIds->isEmpty()) {
            return collect();
        }

        return Humans::query()
            ->whereIn('id', $selectedIds)
            ->orderBy('code')
            ->get(['id', 'code']);
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
