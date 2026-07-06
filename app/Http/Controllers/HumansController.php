<?php

namespace App\Http\Controllers;

use App\Models\Countries;
use App\Models\Humans;
use App\Models\Projects;
use App\Services\HumanSamplesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HumansController extends Controller
{
    protected $service;

    public function __construct(HumanSamplesService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        return view('samples.humans.modals.form_human');
    }

    public function store(Request $request)
    {
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);

        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'field_label' => 'nullable|string|max:150',
            'sex' => 'nullable|string|in:Male,Female,Other',
            'date_of_birth' => 'nullable|date',
            'ethnicity' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'human_country' => 'string|max:255',
            'preferred_contact_method' => 'nullable|string|in:phone,email,sms',
            'phone' => 'nullable|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'alternate_email' => 'nullable|email|max:255',
            'national_id' => 'nullable|string|max:50',
            'marital_status' => 'nullable|string|max:50',
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_id' => 'nullable|string|max:50',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,tif,tiff,pdf|max:51200', // 50MB max
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('human_photos', 'local');
            }

            $existingCodes = Humans::where('code', 'like', $project->code.'-HU-%')
                ->pluck('code');

            $usedNumbers = $existingCodes->map(function ($code) {
                preg_match('/-HU-(\d+)$/', $code, $matches);

                return isset($matches[1]) ? (int) $matches[1] : null;
            })->filter()->sort()->values();

            $newSerial = 1;
            foreach ($usedNumbers as $num) {
                if ($num != $newSerial) {
                    break;
                }
                $newSerial++;
            }

            $human_code = $project->code.'-HU-'.$newSerial;

            $countries_id = $this->service->check_or_create(Countries::class, ['name' => request('human_country')]);

            Humans::create([
                'projects_id' => $projectId,
                'people_id' => Auth::user()?->people?->id,
                'code' => $human_code,
                'field_label' => $request->filled('field_label') ? (string) $request->string('field_label')->trim() : null,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'sex' => $request->sex,
                'date_of_birth' => $request->date_of_birth,
                'ethnicity' => $request->ethnicity,
                'occupation' => $request->occupation,
                'street' => $request->street,
                'city' => $request->city,
                'province' => $request->province,
                'postal_code' => $request->postal_code,
                'countries_id' => $countries_id,
                'preferred_contact_method' => $request->preferred_contact_method,
                'phone' => $request->phone,
                'alternate_phone' => $request->alternate_phone,
                'email' => $request->email,
                'alternate_email' => $request->alternate_email,
                'national_id' => $request->national_id,
                'marital_status' => $request->marital_status,
                'insurance_provider' => $request->insurance_provider,
                'insurance_id' => $request->insurance_id,
                'photo_path' => $photoPath,
            ]);

            session()->flash('success', 'Patient registered successfully!');

            NotificationController::create(
                'human_created',
                'New Patient',
                Auth::user()->people->first_name.' registered a new patient',
                '/samples/humans/list',
                $projectId
            );

            return back();
        } catch (\Exception $e) {
            session()->flash('error', 'An unexpected error occurred: '.$e->getMessage());

            return back()->withInput();
        }
    }
}
