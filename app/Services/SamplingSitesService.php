<?php

namespace App\Services;

use App\Models\Countries;
use App\Models\Organizations;
use App\Models\SamplingSites;

class SamplingSitesService
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
        $defaultSiteTypes = collect([
            'Hospital',
            'Clinic',
            'Natural Park',
            'Farm',
            'Zoo',
            'Sanctuary',
            'National Park',
            'Private Reserve',
            'Game Reserve',
            'Conservation Area',
            'Laboratory',
            'Research Station',
            'Wildlife Center',
            'Veterinary Clinic',
            'Field Station',
            'Other',
        ]);

        $existingSiteTypes = SamplingSites::query()
            ->whereNotNull('site_type')
            ->where('site_type', '!=', '')
            ->distinct()
            ->pluck('site_type');

        $siteTypes = $defaultSiteTypes
            ->merge($existingSiteTypes)
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

        $samplingSites = SamplingSites::query()
            ->with('countries')
            ->get()
            ->sortBy([
                fn ($site) => mb_strtolower((string) optional($site->countries)->name),
                fn ($site) => mb_strtolower((string) $site->name),
            ])
            ->values();

        $samplingSitesByCountry = $samplingSites->groupBy(function ($site): string {
            $country = optional($site->countries)->name;

            return is_string($country) && trim($country) !== '' ? trim($country) : 'Unassigned Country';
        })->sortKeysUsing('strcasecmp');

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

        return [
            'countries' => Countries::orderBy('name')->get(),
            'site_types' => $siteTypes,
            'organizations' => $organizations,
            'organizations_by_country' => $organizationsByCountry,
            'sampling_sites' => $samplingSites,
            'sampling_sites_by_country' => $samplingSitesByCountry,
            'organization_types' => $organizationTypes,
        ];
    }
}
