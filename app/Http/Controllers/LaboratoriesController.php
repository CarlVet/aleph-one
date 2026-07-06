<?php

namespace App\Http\Controllers;

use App\Models\Countries;
use App\Models\Laboratories;
use App\Services\LaboratoriesService;
use App\Support\NameFormatter;
use Illuminate\Support\Facades\Validator;

class LaboratoriesController extends Controller
{
    protected $service;

    public function __construct(LaboratoriesService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        return view('modals.form_laboratories', $this->service->assign());
    }

    public function store()
    {
        $data = request()->all();
        $data['place_name'] = NameFormatter::titleCaseWithMinorWords($data['place_name'] ?? null);
        $data['lab_type'] = NameFormatter::titleCaseWithMinorWords($data['lab_type'] ?? null);
        $data['lab_country'] = NameFormatter::titleCaseWithMinorWords($data['lab_country'] ?? null);
        $data['lab_region'] = NameFormatter::titleCaseWithMinorWords($data['lab_region'] ?? null);
        $data['lab_city'] = NameFormatter::titleCaseWithMinorWords($data['lab_city'] ?? null);

        $rules = [
            'place_name' => 'required|string|max:255|unique:laboratories,name',
            'lab_description' => 'nullable|string|max:1000',
            'lab_type' => 'required|string|max:100',
            'lab_organization' => 'nullable|exists:organizations,id',
            'lab_country' => 'required',
            'lab_region' => 'nullable|string|max:100',
            'lab_city' => 'nullable|string|max:100',
            'lab_address' => 'required|string|max:255',
            'lab_latitude' => 'nullable|numeric|between:-90,90',
            'lab_longitude' => 'nullable|numeric|between:-180,180',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {

            $countries_id = $this->service->check_or_create(Countries::class, ['name' => $data['lab_country']]);

            Laboratories::create([
                'name' => $data['place_name'],
                'description' => $data['lab_description'] ?? null,
                'lab_type' => $data['lab_type'],
                'organizations_id' => $data['lab_organization'] ?? null,
                'countries_id' => $countries_id,
                'region' => $data['lab_region'] ?? null,
                'city' => $data['lab_city'] ?? null,
                'address' => $data['lab_address'] ?? null,
                'latitude' => filled($data['lab_latitude'] ?? null) ? (float) $data['lab_latitude'] : null,
                'longitude' => filled($data['lab_longitude'] ?? null) ? (float) $data['lab_longitude'] : null,
            ]);

            session()->flash('success', 'Laboratory registered successfully!');

            return back();
        } catch (\Exception $e) {
            session()->flash('error', 'An unexpected error occurred: '.$e->getMessage());

            return back()->withInput();
        }
    }
}
