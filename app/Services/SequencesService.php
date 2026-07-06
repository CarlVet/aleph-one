<?php

namespace App\Services;

use App\Models\Countries;
use App\Models\Laboratories;
use App\Models\Organizations;
use App\Models\People;
use App\Models\Projects;
use App\Models\Tubes;

class SequencesService
{
    public function assign(): array
    {
        $projectId = session('selected_project_id');

        $people = $projectId
            ? (Projects::find($projectId)?->people ?? collect())
            : People::all();

        return [
            'people' => $people,
            'laboratories' => Laboratories::all(),
            'methods' => [
                'Sanger sequencing',
                'Next generation sequencing',
                'Whole genome sequencing',
            ],
            'instruments' => [
                'Illumina',
                'PacBio',
                'Oxford Nanopore',
                'ABI 3730',
                'Ion Torrent',
                'Roche 454',
                'ABI 3130',
                'ABI 3100',
                'ABI 3500',
                'Illumina MiSeq',
                'Illumina HiSeq',
                'Illumina NovaSeq',
            ],
        ];
    }

    public function dataForCreate(): array
    {
        $projectId = session('selected_project_id');

        $selectedNucleicTubeIds = array_values(array_filter((array) old('nucleic_tube_id', [])));

        $laboratories = Laboratories::query()
            ->with('countries')
            ->get()
            ->sortBy([
                fn ($lab) => mb_strtolower((string) optional($lab->countries)->name),
                fn ($lab) => mb_strtolower((string) $lab->name),
            ])
            ->values();

        $laboratoriesByCountry = $laboratories->groupBy(function ($laboratory): string {
            $country = optional($laboratory->countries)->name;

            return is_string($country) && trim($country) !== '' ? trim($country) : 'Unassigned Country';
        })->sortKeysUsing('strcasecmp');

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

        $defaultLabTypes = collect([
            'Academic',
            'Research',
            'Diagnostic',
            'Commercial',
            'Government',
        ]);
        $existingLabTypes = Laboratories::query()
            ->whereNotNull('lab_type')
            ->where('lab_type', '!=', '')
            ->distinct()
            ->pluck('lab_type');
        $labTypes = $defaultLabTypes
            ->merge($existingLabTypes)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique(fn ($value) => mb_strtolower($value))
            ->sortBy(fn ($value) => mb_strtolower($value))
            ->values();

        return [
            'people' => Projects::find($projectId)?->people ?? collect(),
            'laboratories' => $laboratories,
            'laboratories_by_country' => $laboratoriesByCountry,
            'organizations' => $organizations,
            'organizations_by_country' => $organizationsByCountry,
            'countries' => Countries::query()->orderBy('name')->get(),
            'organization_types' => $organizationTypes,
            'lab_types' => $labTypes,
            'methods' => [
                'Sanger sequencing',
                'Next generation sequencing',
                'Whole genome sequencing',
            ],
            'instruments' => [
                'Illumina',
                'PacBio',
                'Oxford Nanopore',
                'ABI 3730',
                'Ion Torrent',
                'Roche 454',
                'ABI 3130',
                'ABI 3100',
                'ABI 3500',
                'Illumina MiSeq',
                'Illumina HiSeq',
                'Illumina NovaSeq',
            ],
            'selected_nucleic_tubes' => $selectedNucleicTubeIds
                ? Tubes::query()->where('projects_id', $projectId)->whereIn('id', $selectedNucleicTubeIds)->get(['id', 'code'])
                : collect(),
        ];
    }
}
