<?php

namespace App\Http\Controllers;

use App\Models\Departments;
use App\Models\Organizations;
use App\Support\NameFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentsController extends Controller
{
    public function create()
    {
        $organizations = Organizations::query()
            ->with('countries')
            ->get()
            ->sortBy([
                fn ($org) => mb_strtolower((string) optional($org->countries)->name),
                fn ($org) => mb_strtolower((string) $org->name),
            ])
            ->values();

        $organizationsByCountry = $organizations->groupBy(function ($organization): string {
            $country = optional($organization->countries)->name;

            return is_string($country) && trim($country) !== '' ? trim($country) : 'Unassigned Country';
        })->sortKeysUsing('strcasecmp');
        $defaultDepartmentTypes = collect([
            'Academic',
            'Administrative',
            'Bioinformatics',
            'Clinical',
            'Diagnostic',
            'Epidemiology',
            'Field Operations',
            'Molecular Biology',
            'Research',
            'Veterinary',
        ]);

        $existingDepartmentTypes = Departments::query()
            ->whereNotNull('department_type')
            ->where('department_type', '!=', '')
            ->distinct()
            ->pluck('department_type');

        $departmentTypes = $defaultDepartmentTypes
            ->merge($existingDepartmentTypes)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique(fn ($value) => mb_strtolower($value))
            ->sortBy(fn ($value) => mb_strtolower($value))
            ->values();

        return view('modals.form_departments', compact('organizations', 'organizationsByCountry', 'departmentTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['department_name'] = NameFormatter::titleCaseWithMinorWords($data['department_name'] ?? null);
        $data['department_type'] = NameFormatter::titleCaseWithMinorWords($data['department_type'] ?? null);
        $data['building'] = NameFormatter::titleCaseWithMinorWords($data['building'] ?? null);

        $rules = [
            'department_name' => 'required|string|max:255|unique:departments,name',
            'department_type' => 'nullable|string|max:255',
            'building' => 'nullable|string|max:255',
            'organization_id' => 'required|exists:organizations,id',
            'description' => 'nullable|string|max:1000',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {
            Departments::create([
                'name' => $data['department_name'],
                'department_type' => $data['department_type'] ?? null,
                'building' => $data['building'] ?? null,
                'organizations_id' => $data['organization_id'],
                'description' => $data['description'] ?? null,
            ]);

            session()->flash('success', 'Department registered successfully!');

            return back();
        } catch (\Exception $e) {
            session()->flash('error', 'An unexpected error occurred: '.$e->getMessage());

            return back()->withInput();
        }
    }
}
