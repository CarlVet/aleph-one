<?php

namespace App\Http\Controllers;

use App\Models\Laboratories;
use App\Models\Locations;
use App\Services\LocationsService;
use Illuminate\Support\Facades\Validator;

class LocationsController extends Controller
{
    protected $service;

    public function __construct(LocationsService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        return view('modals.form_locations', $this->service->assign());
    }

    public function store()
    {
        $rules = [
            'location_name' => 'required|string|max:255|unique:locations,name',
            'location_type' => 'nullable|string|max:100',
            'lab' => 'nullable|string|max:255',
            'room' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
        ];

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        $lab_id = Laboratories::where('name', request('lab'))->first()->id;

        try {
            Locations::create([
                'name' => request('location_name'),
                'type' => request('location_type'),
                'laboratories_id' => $lab_id,
                'room' => request('room'),
                'barcode' => request('barcode'),
            ]);

            session()->flash('success', 'Storage location registered successfully!');

            return back();
        } catch (\Exception $e) {
            session()->flash('error', 'An unexpected error occurred: '.$e->getMessage());

            return back()->withInput();
        }
    }
}
