<?php

namespace App\Http\Controllers;

use App\Models\Countries;
use App\Models\SamplingSites;
use App\Services\SamplingSitesService;
use App\Support\NameFormatter;
use Illuminate\Support\Facades\Validator;

class SamplingSitesController extends Controller
{
    protected $service;

    public function __construct(SamplingSitesService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        return view('modals.form_sampling_sites', $this->service->assign());
    }

    public function store()
    {
        $data = request()->all();
        $data['site_name'] = NameFormatter::titleCaseWithMinorWords($data['site_name'] ?? null);
        $data['site_type'] = NameFormatter::titleCaseWithMinorWords($data['site_type'] ?? null);
        $data['sampling_country'] = NameFormatter::titleCaseWithMinorWords($data['sampling_country'] ?? null);
        $data['region'] = NameFormatter::titleCaseWithMinorWords($data['region'] ?? null);
        $data['city'] = NameFormatter::titleCaseWithMinorWords($data['city'] ?? null);
        $data['province'] = NameFormatter::titleCaseWithMinorWords($data['province'] ?? null);

        $rules = [
            'site_name' => 'required|string|max:255|unique:sampling_sites,name',
            'description' => 'nullable|string|max:1000',
            'site_type' => 'nullable|string|max:100',
            'organization_id' => 'nullable|exists:organizations,id',
            'sampling_country' => 'required',
            'region' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {

            $countries_id = $this->service->check_or_create(Countries::class, ['name' => $data['sampling_country']]);

            SamplingSites::create([
                'name' => $data['site_name'],
                'description' => $data['description'] ?? null,
                'site_type' => $data['site_type'] ?? null,
                'organizations_id' => $data['organization_id'] ?? null,
                'countries_id' => $countries_id,
                'region' => $data['region'] ?? null,
                'city' => $data['city'] ?? null,
                'province' => $data['province'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);

            session()->flash('success', 'Sampling site registered successfully!');

            return back();
        } catch (\Exception $e) {
            session()->flash('error', 'An unexpected error occurred: '.$e->getMessage());

            return back()->withInput();
        }
    }
}
