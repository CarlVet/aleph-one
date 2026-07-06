<?php

namespace App\Http\Controllers;

use App\Models\Pathogens;
use App\Services\ExperimentsService;
use Illuminate\Support\Facades\Validator;

class PathogensController extends Controller
{
    protected $service;

    public function __construct(ExperimentsService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        return view('pathogens.create', $this->service->assign());
    }

    public function store()
    {

        $rules = [
            'ncbi_tax_id' => 'nullable|integer|min:1',
            'pathogen_species' => 'string|max:100|required|unique:pathogens,species',
            'pathogen_genus' => 'string|max:100|required',
            'pathogen_family' => 'string|max:200|required',
            'pathogen_order' => 'string|max:200|required',
            'pathogen_class' => 'string|max:200|required',
            'pathogen_phylum' => 'string|max:200|required',
            'pathogen_kingdom' => 'string|max:200|required',
            'pathogen_domain' => 'string|max:200|required',
        ];

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {

            Pathogens::create([
                'ncbi_tax_id' => request('ncbi_tax_id'),
                'species' => request('pathogen_species'),
                'genus' => request('pathogen_genus'),
                'family' => request('pathogen_family'),
                'order' => request('pathogen_order'),
                'class' => request('pathogen_class'),
                'phylum' => request('pathogen_phylum'),
                'kingdom' => request('pathogen_kingdom'),
                'domain' => request('pathogen_domain'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Flash success message to the session
            session()->flash('success', 'Pathogen registered successfully!');

            return back();
        } catch (\Exception $e) {
            // Handle any exceptions during registration
            session()->flash('error', 'An error occurred: '.$e->getMessage());

            return back()->withInput();
        }
    }
}
