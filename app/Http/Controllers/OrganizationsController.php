<?php

namespace App\Http\Controllers;

use App\Models\Countries;
use App\Models\Organizations;
use App\Services\OrganizationsService;
use App\Support\NameFormatter;
use Illuminate\Support\Facades\Validator;

class OrganizationsController extends Controller
{
    protected $service;

    public function __construct(OrganizationsService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        return view('modals.form_organizations', $this->service->assign());
    }

    public function store()
    {
        $data = request()->all();
        $data['organization_name'] = NameFormatter::titleCaseWithMinorWords($data['organization_name'] ?? null);
        $data['organization_type'] = NameFormatter::titleCaseWithMinorWords($data['organization_type'] ?? null);
        $data['organization_country'] = NameFormatter::titleCaseWithMinorWords($data['organization_country'] ?? null);
        $data['region'] = NameFormatter::titleCaseWithMinorWords($data['region'] ?? null);
        $data['city'] = NameFormatter::titleCaseWithMinorWords($data['city'] ?? null);

        $rules = [
            'organization_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'organization_type' => 'required|string|max:100',
            'website' => 'nullable|url|max:255',
            'organization_country' => 'required',
            'region' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:1000',
        ];

        $validator = Validator::make($data, $rules);

        $validator->after(function ($validator) use ($data): void {
            $name = trim((string) ($data['organization_name'] ?? ''));
            if ($name === '') {
                return;
            }

            $exists = Organizations::query()
                ->whereRaw('lower(trim(name)) = ?', [mb_strtolower($name)])
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'organization_name',
                    'An organization with this name already exists. Choose it from the dropdown instead.'
                );
            }
        });

        if ($validator->fails()) {
            if (request()->expectsJson() || request()->is('profile/organizations')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {
            $countries_id = $this->service->check_or_create(Countries::class, ['name' => $data['organization_country']]);

            Organizations::create([
                'name' => $data['organization_name'],
                'description' => $data['description'] ?? null,
                'type' => $data['organization_type'],
                'website' => $data['website'] ?? null,
                'countries_id' => $countries_id,
                'region' => $data['region'] ?? null,
                'city' => $data['city'] ?? null,
                'address' => $data['address'] ?? null,
            ]);

            if (request()->expectsJson() || request()->is('profile/organizations')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Organization registered successfully!',
                ]);
            }

            session()->flash('success', 'Organization registered successfully!');

            return back();
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('profile/organizations')) {
                return response()->json([
                    'success' => false,
                    'message' => 'An unexpected error occurred: '.$e->getMessage(),
                ], 500);
            }

            session()->flash('error', 'An unexpected error occurred: '.$e->getMessage());

            return back()->withInput();
        }
    }
}
