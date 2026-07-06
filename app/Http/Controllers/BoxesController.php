<?php

namespace App\Http\Controllers;

use App\Models\Boxes;
use App\Models\BoxPositions;
use App\Models\Projects;
use App\Services\BoxesService;
use App\Support\ProjectPermission;
use App\Support\SubProjectFlag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BoxesController extends Controller
{
    protected $boxesService;

    public function __construct(BoxesService $boxesService)
    {
        $this->boxesService = $boxesService;
    }

    public function create()
    {
        $boxesData = $this->boxesService->assign();
        $projectId = session('selected_project_id');
        $user = Auth::user();

        return view('bank.boxes.create', array_merge($boxesData, [
            'can_assign_registrar' => $user ? ProjectPermission::canAssignRegistrar($user, (int) $projectId) : false,
            'locked_registrar_people_id' => $user ? ProjectPermission::currentRegistrarPeopleId($user) : null,
            'sub_project_options' => SubProjectFlag::optionsForUser($user, (int) $projectId),
        ]));
    }

    public function create_positions()
    {
        $boxesData = $this->boxesService->assign();
        $projectId = session('selected_project_id');
        $user = Auth::user();

        $data = array_merge($boxesData, [
            'can_assign_registrar' => $user ? ProjectPermission::canAssignRegistrar($user, (int) $projectId) : false,
            'locked_registrar_people_id' => $user ? ProjectPermission::currentRegistrarPeopleId($user) : null,
            'sub_project_options' => SubProjectFlag::optionsForUser($user, (int) $projectId),
        ]);

        return view('bank.boxes.positions', $data);
    }

    public function store_boxes()
    {
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);
        $project_code = $project->code;

        $rules = [
            'box_name' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('boxes', 'name')->where(fn ($query) => $query->where('projects_id', $projectId)),
            ],
            'n_columns' => 'required|integer|min:6',
            'n_rows' => 'required|integer|min:6',
            'alias_code' => 'nullable|string|max:255',
        ];

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {

            $existingCodes = Boxes::where('projects_id', $projectId)
                ->where('code', 'like', $project_code.'-BO-%')
                ->pluck('code');

            $usedNumbers = $existingCodes->map(function ($code) {
                preg_match('/-BO-(\d+)$/', $code, $matches);

                return isset($matches[1]) ? (int) $matches[1] : null;
            })->filter()->sort()->values();

            $newSerial = 1;
            foreach ($usedNumbers as $num) {
                if ($num != $newSerial) {
                    break;
                }
                $newSerial++;
            }

            $bo_code = $project_code.'-BO-'.$newSerial;

            Boxes::create([
                'code' => $bo_code,
                'alias_code' => request('alias_code') ?: null,
                'name' => request('box_name'),
                'n_columns' => request('n_columns'),
                'n_rows' => request('n_rows'),
                'projects_id' => $projectId,
            ]);

            // Get the authenticated user
            $user = Auth::user();

            // After successfully updating box positions
            NotificationController::create(
                'box_created',
                'New Box',
                $user->people->first_name.' registered a new box',
                '/bank/boxes/list',
                $projectId
            );

            session()->flash('success', 'Box registered successfully with code: '.$bo_code);

            return back();
        } catch (\Exception $e) {
            session()->flash('error', 'An unexpected error occurred. Please try again.');

            return back()->withInput();
        }
    }

    public function store_positions()
    {
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);
        $project_code = $project->code;

        $rules = [
            'box' => 'required|exists:boxes,id',
            'location' => 'required|exists:locations,id',
            'date' => 'required|date',
            'mover' => 'required|exists:people,id',
            'reason' => 'nullable|string|max:255',
            'sub_project_id' => 'nullable|integer|exists:sub_projects,id',
        ];

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {
            $boxes = request('box');
            $moverPeopleId = $this->resolveRegistrarPeopleId('mover');
            $selectedSubProjectId = request('sub_project_id') ? (int) request('sub_project_id') : null;
            if (! SubProjectFlag::isSelectableByUser(Auth::user(), (int) $projectId, $selectedSubProjectId)) {
                session()->flash('error', 'Selected sub-project is not allowed for your user.');

                return back()->withInput();
            }

            foreach ($boxes as $boxId) {
                $boxPosition = BoxPositions::create([
                    'boxes_id' => $boxId,
                    'locations_id' => request('location'),
                    'sublocation' => request('sub_location'),
                    'date_moved' => request('date'),
                    'people_id' => $moverPeopleId,
                    'reason' => request('reason'),
                ]);
                SubProjectFlag::assign($boxPosition, $selectedSubProjectId);

            }

            // After successfully updating box positions
            NotificationController::create(
                'box_moved',
                'Boxes Moved',
                'Box positions have been updated in the storage system.',
                '/bank/boxes/list',
                $projectId
            );

            return redirect('/bank/boxes/create')->with('success', 'Box positions updated successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'An unexpected error occurred. Please try again.');

            return back()->withInput();
        }
    }

    private function resolveRegistrarPeopleId(string $requestKey): ?int
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        $projectId = session('selected_project_id');
        if (ProjectPermission::canAssignRegistrar($user, (int) $projectId)) {
            return request($requestKey) ? (int) request($requestKey) : null;
        }

        return ProjectPermission::currentRegistrarPeopleId($user);
    }
}
