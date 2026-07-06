<?php

namespace App\Services;

use App\Models\Countries;
use App\Models\Organizations;

class OrganizationsService
{
    protected $projectId;

    public function __construct()
    {
        $this->projectId = session('selected_project_id');
    }

    public function getProjectId()
    {
        return $this->projectId;
    }

    public function isGuestMode()
    {
        return $this->projectId === null;
    }

    public function check_or_create($model, $conditions, $attributes = [])
    {
        $existing_value = $model::where($conditions)->first();

        if (! $existing_value) {
            $model::create(array_merge($conditions, $attributes));

            return $model::where($conditions)->first()->id;
        } else {
            return $existing_value->id;
        }
    }

    public function assign()
    {
        $defaultOrganizationTypes = collect([
            'Government Agency',
            'Research Institute',
            'University',
            'Non-Profit Organization',
            'Private Company',
            'Zoo',
            'Wildlife Sanctuary',
            'Veterinary Clinic',
            'Laboratory',
            'Conservation Organization',
            'National Park',
            'Game Reserve',
            'Museum',
            'Hospital',
            'Pharmaceutical Company',
            'Biotechnology Company',
        ]);

        $existingOrganizationTypes = Organizations::query()
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->distinct()
            ->pluck('type');

        $organizationTypes = $defaultOrganizationTypes
            ->merge($existingOrganizationTypes)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique(fn ($value) => mb_strtolower($value))
            ->sortBy(fn ($value) => mb_strtolower($value))
            ->values();

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

        return [
            'organizations' => $organizations,
            'organizations_by_country' => $organizationsByCountry,
            'countries' => Countries::query()->orderBy('name')->get(),
            'organization_types' => $organizationTypes,
            'current_project_id' => $this->projectId,
        ];
    }
}
