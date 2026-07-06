<?php

namespace App\Http\Controllers;

use App\Models\Projects;
use App\Services\MicroplasticsService;
use App\Support\ProjectPermission;
use App\Support\SubProjectFlag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MicroplasticsController extends Controller
{
    public function __construct(
        protected MicroplasticsService $service
    ) {}

    public function create()
    {
        $projectId = (int) session('selected_project_id');
        $user = Auth::user();

        return view('samples.microplastics.create', array_merge(
            $this->service->dataForCreate(),
            [
                'selected_project_id' => $projectId,
                'can_assign_registrar' => $user ? ProjectPermission::canAssignRegistrar($user, $projectId) : false,
                'locked_registrar_people_id' => $user ? ProjectPermission::currentRegistrarPeopleId($user) : null,
                'sub_project_options' => SubProjectFlag::optionsForUser($user, $projectId),
            ]
        ));
    }

    public function store()
    {
        if ((string) request('register_mode', 'form') === 'table') {
            return $this->storeFromTable();
        }

        $projectId = (int) session('selected_project_id');
        $project = Projects::query()->findOrFail($projectId);

        $rules = [
            'model' => 'required|string',
            'sample_weight' => 'nullable|numeric|min:0',
            'r_coeff' => 'nullable|numeric|between:-1,1',
            'mps_type' => 'required|array|min:1',
            'mps_type.*' => 'required|string|max:255|exists:mps_types,name',
            'm_feret' => 'nullable|numeric|min:0',
            'identification_date' => 'required|date',
            'protocol' => 'required|string|max:255',
            'microplastics_lab' => 'required|string|max:255',
            'identifier' => 'required|exists:people,id',
            'source_measurement_mode' => 'required|in:pooled,separate_measurements',
            'sub_project_id' => 'nullable|integer|exists:sub_projects,id',
            'project_id_snapshot' => 'required|integer|exists:projects,id',
        ];

        $model = (string) request('model');
        $requestKey = $this->service->requestKeyForModelLabel($model);
        if ($requestKey !== null) {
            $rules[$requestKey] = 'required|array|min:1';
            $rules[$requestKey.'.*'] = 'required|integer|exists:tubes,id';
        }

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        if ((int) request('project_id_snapshot') !== $projectId) {
            session()->flash('error', 'The selected project changed while this form was open. Please reload the page and submit again.');

            return back()->withInput();
        }

        $sourceType = $this->service->sourceTypeForModelLabel($model);
        if ($sourceType === null || $requestKey === null) {
            session()->flash('error', 'Invalid source type selected for microplastics registration.');

            return back()->withInput();
        }

        $selectedSubProjectId = request('sub_project_id') ? (int) request('sub_project_id') : null;
        if (! SubProjectFlag::isSelectableByUser(Auth::user(), $projectId, $selectedSubProjectId)) {
            session()->flash('error', 'Selected sub-project is not allowed for your user.');

            return back()->withInput();
        }

        try {
            $identifiedByPeopleId = $this->resolveRegistrarPeopleId('identifier');
            $createdCount = $this->service->registerFromTubes(
                $projectId,
                $sourceType,
                (array) request($requestKey, []),
                (string) request('protocol'),
                (string) request('microplastics_lab'),
                $identifiedByPeopleId,
                [
                    'sample_weight' => request('sample_weight'),
                    'r_coeff' => request('r_coeff'),
                    'mps_type' => request('mps_type'),
                    'm_feret' => request('m_feret'),
                    'identification_date' => (string) request('identification_date'),
                    'source_measurement_mode' => request('source_measurement_mode'),
                ],
                $selectedSubProjectId
            );

            session()->flash('success', $createdCount > 1
                ? "{$createdCount} microplastics records registered successfully!"
                : 'Microplastic registered successfully!');

            $user = Auth::user();
            NotificationController::create(
                'microplastics_created',
                'New Microplastics Identification',
                $user->people->first_name.' registered '.$createdCount.' microplastics record(s).',
                '/samples/microplastics/list',
                $projectId
            );

            return back();
        } catch (\Throwable $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());

            return back()->withInput();
        }
    }

    private function storeFromTable()
    {
        $projectId = (int) session('selected_project_id');
        $rows = array_values(array_filter((array) request('table_rows', []), function ($row): bool {
            return is_array($row) && array_filter($row, fn ($value) => $value !== null && $value !== '') !== [];
        }));

        $validator = Validator::make(
            ['table_rows' => $rows, 'sub_project_id' => request('sub_project_id')],
            [
                'table_rows' => 'required|array|min:1',
                'table_rows.*.tube_id' => 'required|integer|exists:tubes,id',
                'table_rows.*.sample_weight' => 'nullable|numeric|min:0',
                'table_rows.*.r_coeff' => 'nullable|numeric|between:-1,1',
                'table_rows.*.mps_type' => 'required|string|max:255',
                'table_rows.*.m_feret' => 'nullable|numeric|min:0',
                'table_rows.*.identification_date' => 'required|date',
                'table_rows.*.protocol_name' => 'required|string|max:255',
                'table_rows.*.laboratory' => 'required|string|max:255',
                'table_rows.*.identified_by' => 'required|integer|exists:people,id',
                'sub_project_id' => 'nullable|integer|exists:sub_projects,id',
            ]
        );

        if ($validator->fails()) {
            session()->flash('error', 'Table registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        $selectedSubProjectId = request('sub_project_id') ? (int) request('sub_project_id') : null;
        if (! SubProjectFlag::isSelectableByUser(Auth::user(), $projectId, $selectedSubProjectId)) {
            session()->flash('error', 'Selected sub-project is not allowed for your user.');

            return back()->withInput();
        }

        try {
            $createdCount = $this->service->registerFromTableRows($projectId, $rows, $selectedSubProjectId);

            session()->flash('success', $createdCount > 1
                ? "{$createdCount} microplastics records registered successfully!"
                : 'Microplastic registered successfully!');

            $user = Auth::user();
            NotificationController::create(
                'microplastics_created',
                'New Microplastics Identification',
                $user->people->first_name.' registered '.$createdCount.' microplastics record(s).',
                '/samples/microplastics/list',
                $projectId
            );

            return back();
        } catch (\Throwable $e) {
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
