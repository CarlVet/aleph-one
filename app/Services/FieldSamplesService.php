<?php

namespace App\Services;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\Tubes;

class FieldSamplesService
{
    public function dataForFieldProcessingForm(): array
    {
        $projectId = session('selected_project_id');

        $selectedHumanIds = array_values(array_filter((array) old('human_sample_select', [])));
        $selectedAnimalIds = array_values(array_filter((array) old('animal_sample_select', [])));
        $selectedEnvironmentIds = array_values(array_filter((array) old('environment_sample_select', [])));
        $selectedParasiteIds = array_values(array_filter((array) old('parasite_sample_select', [])));
        $selectedNucleicIds = array_values(array_filter((array) old('nucleic_acid_select', [])));
        $selectedCultureIds = array_values(array_filter((array) old('culture_select', [])));
        $selectedPoolIds = array_values(array_filter((array) old('pool_select', [])));

        $tubeTypeDefaults = [
            'Eppendorf 1.5ml',
            'Eppendorf 2.0ml',
            'Cryovial 1.8ml',
            'Cryovial 2.0ml',
            'Falcon 15ml',
            'Falcon 50ml',
        ];
        $purposeDefaults = [
            'DNA extraction',
            'RNA extraction',
            'Protein analysis',
            'Microbial culture',
            'Parasite identification',
            'Storage',
        ];
        $preservantDefaults = [
            'None',
            '100% ethanol',
            '70% ethanol',
            'PBS',
            'Glycerol',
            'DMSO',
            'RNAlater',
            '10% formalin',
            '4% PFA',
        ];
        $amountUnitDefaults = ['ml', 'μl', 'mg', 'g', 'pieces'];

        $tubeTypeDataset = Tubes::query()
            ->where('projects_id', $projectId)
            ->whereNotNull('tube_type')
            ->distinct()
            ->orderBy('tube_type')
            ->pluck('tube_type')
            ->filter(fn ($v) => filled($v))
            ->values()
            ->all();

        $purposeDataset = Tubes::query()
            ->where('projects_id', $projectId)
            ->whereNotNull('purpose')
            ->distinct()
            ->orderBy('purpose')
            ->pluck('purpose')
            ->filter(fn ($v) => filled($v))
            ->values()
            ->all();

        $preservantDataset = Tubes::query()
            ->where('projects_id', $projectId)
            ->whereNotNull('preservant')
            ->distinct()
            ->orderBy('preservant')
            ->pluck('preservant')
            ->filter(fn ($v) => filled($v))
            ->values()
            ->all();

        $amountUnitDataset = Tubes::query()
            ->where('projects_id', $projectId)
            ->whereNotNull('amount_unit')
            ->distinct()
            ->orderBy('amount_unit')
            ->pluck('amount_unit')
            ->filter(fn ($v) => filled($v))
            ->values()
            ->all();

        $tube_type_options = array_values(array_unique(array_merge($tubeTypeDefaults, $tubeTypeDataset)));
        $purpose_options = array_values(array_unique(array_merge($purposeDefaults, $purposeDataset)));
        $preservant_options = array_values(array_unique(array_merge($preservantDefaults, $preservantDataset)));
        $amount_unit_options = array_values(array_unique(array_merge($amountUnitDefaults, $amountUnitDataset)));

        return [
            'selected_human_samples' => $selectedHumanIds
                ? HumanSamples::query()->where('projects_id', $projectId)->whereIn('id', $selectedHumanIds)->get(['id', 'code'])
                : collect(),
            'selected_animal_samples' => $selectedAnimalIds
                ? AnimalSamples::query()->where('projects_id', $projectId)->whereIn('id', $selectedAnimalIds)->get(['id', 'code'])
                : collect(),
            'selected_environment_samples' => $selectedEnvironmentIds
                ? EnvironmentSamples::query()->where('projects_id', $projectId)->whereIn('id', $selectedEnvironmentIds)->get(['id', 'code'])
                : collect(),
            'selected_parasite_samples' => $selectedParasiteIds
                ? ParasiteSamples::query()->where('projects_id', $projectId)->whereIn('id', $selectedParasiteIds)->get(['id', 'code'])
                : collect(),
            'selected_nucleic_acids' => $selectedNucleicIds
                ? NucleicAcids::query()->where('projects_id', $projectId)->whereIn('id', $selectedNucleicIds)->get(['id', 'code'])
                : collect(),
            'selected_cultures' => $selectedCultureIds
                ? Cultures::query()->where('projects_id', $projectId)->whereIn('id', $selectedCultureIds)->get(['id', 'code'])
                : collect(),
            'selected_pools' => $selectedPoolIds
                ? Pools::query()->where('projects_id', $projectId)->whereIn('id', $selectedPoolIds)->get(['id', 'code'])
                : collect(),
            'tube_type_options' => $tube_type_options,
            'purpose_options' => $purpose_options,
            'preservant_options' => $preservant_options,
            'amount_unit_options' => $amount_unit_options,
        ];
    }
}
