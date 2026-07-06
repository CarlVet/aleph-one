<?php

namespace App\Services;

use App\Models\AnimalSpecies;
use App\Models\ClinicalSigns;
use App\Models\Countries;
use App\Models\EnvironmentSampleTypes;
use App\Models\Lesions;
use App\Models\ParasiteSampleTypes;
use App\Models\ParasiteSpecies;
use App\Models\Pathogens;
use App\Models\People;
use App\Models\Projects;
use App\Models\RiskFactors;
use App\Models\SampleTypes;
use App\Models\Studies;
use App\Models\Techniques;
use App\Support\LookupTableData;

class MetaService
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
        $projectId = $this->getProjectId();

        if ($this->isGuestMode()) {
            $people = People::all();
        } else {
            $people = Projects::find($projectId)->people;
        }

        $selectedStudyId = old('studies_id');
        $selected_study = $selectedStudyId
            ? Studies::query()->whereKey($selectedStudyId)->get(['id', 'ref_key', 'title'])
            : collect();
        $study_designs = Studies::query()
            ->select('study_design')
            ->whereNotNull('study_design')
            ->distinct()
            ->orderBy('study_design')
            ->pluck('study_design')
            ->values();
        $countries = Countries::all();
        $animal_species = AnimalSpecies::all();
        $sample_types = SampleTypes::all();
        $pathogens = Pathogens::all();
        $techniques = Techniques::all();
        $risk_factors = RiskFactors::all();
        $projects = Projects::all();
        $clinical_signs = ClinicalSigns::all();
        $lesions = Lesions::all();
        $parasite_species = ParasiteSpecies::all();
        $parasite_sample_types = ParasiteSampleTypes::all();
        $environment_sample_types = EnvironmentSampleTypes::all();

        return [
            'selected_study' => $selected_study,
            'study_designs' => $study_designs,
            'countries' => $countries,
            'animal_species' => $animal_species,
            'sample_types' => $sample_types,
            'pathogens' => $pathogens,
            'techniques' => $techniques,
            'risk_factors' => $risk_factors,
            'projects' => $projects,
            'clinical_signs' => $clinical_signs,
            'lesions' => $lesions,
            'people' => $people,
            'parasite_species' => $parasite_species,
            'parasite_sample_types' => $parasite_sample_types,
            'environment_sample_types' => $environment_sample_types,
            'pathogen_lookup_rows' => LookupTableData::pathogens(),
            'animal_species_lookup_rows' => LookupTableData::animalSpecies(),
            'parasite_species_lookup_rows' => LookupTableData::parasiteSpecies(),
        ];
    }

    public function getValidationRules($model)
    {
        $baseRules = [
            'studies_id' => 'required|exists:studies,id',
            'countries_id' => 'required',
            'date_sampling' => 'nullable|date',
            'pathogens_id' => 'required|exists:pathogens,id',
            'techniques_id' => 'required',
            'tested_n' => 'required|integer|min:0',
            'pos_n' => 'required|integer|min:0',
            'risk_factors_id' => 'required|array|min:1',
            'risk_factors_id.*' => 'nullable|string|max:255',
            'people_id' => 'required|exists:people,id',
        ];

        switch ($model) {
            case 'MetaAnimal':
                return array_merge($baseRules, [
                    'animal_species_id' => 'required|exists:animal_species,id',
                    'sex' => 'nullable|string',
                    'age_group' => 'nullable|string',
                    'habitat' => 'nullable|string',
                    'location' => 'nullable|string',
                    'sample_types_id' => 'required',
                    'animal_sample_category' => 'nullable|in:host_derived,non_host_derived',
                    'clinical_signs_id' => 'required|array|min:1',
                    'clinical_signs_id.*' => 'nullable|string|max:255',
                    'lesions_id' => 'required|array|min:1',
                    'lesions_id.*' => 'nullable|string|max:255',
                ]);

            case 'MetaHuman':
                return array_merge($baseRules, [
                    'sex' => 'nullable|string',
                    'age_group' => 'nullable|string',
                    'job' => 'nullable|string',
                    'habitat' => 'nullable|string',
                    'location' => 'nullable|string',
                    'human_sample_types_id' => 'required',
                    'human_sample_category' => 'nullable|in:host_derived,non_host_derived',
                    'human_signs_id' => 'required|array|min:1',
                    'human_signs_id.*' => 'nullable|string|max:255',
                    'human_lesions_id' => 'required|array|min:1',
                    'human_lesions_id.*' => 'nullable|string|max:255',
                ]);

            case 'MetaParasite':
                return array_merge($baseRules, [
                    'parasite_species_id' => 'required|exists:parasite_species,id',
                    'sex' => 'nullable|string',
                    'stage' => 'nullable|string',
                    'location' => 'nullable|string',
                    'parasite_sample_types_id' => 'required',
                ]);

            case 'MetaEnvironment':
                return array_merge($baseRules, [
                    'environment_sample_types_id' => 'required',
                    'location' => 'nullable|string',
                ]);

            default:
                throw new \Exception('Invalid model type');
        }
    }
}
