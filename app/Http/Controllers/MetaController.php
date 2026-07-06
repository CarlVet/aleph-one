<?php

namespace App\Http\Controllers;

use App\Models\ClinicalSigns;
use App\Models\Countries;
use App\Models\EnvironmentSampleTypes;
use App\Models\Lesions;
use App\Models\MetaAnimal;
use App\Models\MetaEnvironment;
use App\Models\MetaHuman;
use App\Models\MetaParasite;
use App\Models\ParasiteSampleTypes;
use App\Models\Projects;
use App\Models\RiskFactors;
use App\Models\SampleTypes;
use App\Models\Techniques;
use App\Services\MetaService;
use App\Support\ProjectPermission;
use App\Support\SubProjectFlag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MetaController extends Controller
{
    protected $service;

    public function __construct(MetaService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        $projectId = session('selected_project_id');
        $user = Auth::user();

        return view('meta.create', array_merge($this->service->assign(), [
            'can_assign_registrar' => $user ? ProjectPermission::canAssignRegistrar($user, (int) $projectId) : false,
            'locked_registrar_people_id' => $user ? ProjectPermission::currentRegistrarPeopleId($user) : null,
            'sub_project_options' => SubProjectFlag::optionsForUser($user, (int) $projectId),
        ]));
    }

    public function store(Request $request)
    {
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);
        $project_code = $project->code;

        $model = $request->input('model');

        // Get validation rules based on model
        $rules = $this->service->getValidationRules($model);
        $rules['sub_project_id'] = 'nullable|integer|exists:sub_projects,id';

        $validator = Validator::make($request->all(), $rules);
        $validator->after(function ($validator) use ($model, $request): void {
            if ($model === 'MetaAnimal') {
                $sampleTypeName = trim((string) $request->input('sample_types_id', ''));
                if ($sampleTypeName !== '' && ! $this->sampleTypeExists($sampleTypeName)) {
                    $category = trim((string) $request->input('animal_sample_category', ''));
                    if (! in_array($category, ['host_derived', 'non_host_derived'], true)) {
                        $validator->errors()->add('animal_sample_category', 'Please select whether the sample type is host_derived or non_host_derived.');
                    }
                }
            }

            if ($model === 'MetaHuman') {
                $sampleTypeName = trim((string) $request->input('human_sample_types_id', ''));
                if ($sampleTypeName !== '' && ! $this->sampleTypeExists($sampleTypeName)) {
                    $category = trim((string) $request->input('human_sample_category', ''));
                    if (! in_array($category, ['host_derived', 'non_host_derived'], true)) {
                        $validator->errors()->add('human_sample_category', 'Please select whether the sample type is host_derived or non_host_derived.');
                    }
                }
            }
        });

        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            session()->flash('error', 'Validation failed: '.implode(', ', $errorMessages));

            return back()->withErrors($validator)->withInput();
        }

        $techniques_id = $this->service->check_or_create(
            Techniques::class,
            ['name' => request('techniques_id')],
            ['type' => request('technique_new')]
        );

        $countries_id = $this->service->check_or_create(
            Countries::class,
            ['name' => request('countries_id')],
        );

        $riskFactorIds = $this->resolveMetaTermIds((array) request('risk_factors_id', []), RiskFactors::class);

        try {
            $reviewerPeopleId = $this->resolveRegistrarPeopleId('people_id');
            $selectedSubProjectId = $request->filled('sub_project_id') ? (int) $request->input('sub_project_id') : null;
            if (! SubProjectFlag::isSelectableByUser(Auth::user(), (int) $projectId, $selectedSubProjectId)) {
                session()->flash('error', 'Selected sub-project is not allowed for your user.');

                return back()->withInput();
            }
            switch ($model) {
                case 'MetaAnimal':

                    $sample_types_id = $this->service->check_or_create(
                        SampleTypes::class,
                        ['name' => request('sample_types_id')],
                        $this->sampleTypeCategoryAttributes((string) request('animal_sample_category', ''))
                    );

                    $clinicalSignIds = $this->resolveMetaTermIds((array) request('clinical_signs_id', []), ClinicalSigns::class);
                    $lesionIds = $this->resolveMetaTermIds((array) request('lesions_id', []), Lesions::class);

                    $metaAnimal = MetaAnimal::create([
                        'studies_id' => request('studies_id'),
                        'animal_species_id' => request('animal_species_id'),
                        'sex' => request('sex'),
                        'age_group' => request('age_group'),
                        'habitat' => request('habitat'),
                        'location' => request('location'),
                        'countries_id' => $countries_id,
                        'date_sampling' => request('date_sampling'),
                        'sample_types_id' => $sample_types_id,
                        'pathogens_id' => request('pathogens_id'),
                        'techniques_id' => $techniques_id,
                        'tested_n' => request('tested_n'),
                        'pos_n' => request('pos_n'),
                        'projects_id' => $projectId,
                        'people_id' => $reviewerPeopleId,
                    ]);
                    $metaAnimal->risk_factors()->sync($riskFactorIds);
                    $metaAnimal->clinical_signs()->sync($clinicalSignIds);
                    $metaAnimal->lesions()->sync($lesionIds);
                    SubProjectFlag::assign($metaAnimal, $selectedSubProjectId);
                    break;

                case 'MetaHuman':

                    $sample_types_id = $this->service->check_or_create(
                        SampleTypes::class,
                        ['name' => request('human_sample_types_id')],
                        $this->sampleTypeCategoryAttributes((string) request('human_sample_category', ''))
                    );

                    $clinicalSignIds = $this->resolveMetaTermIds((array) request('human_signs_id', []), ClinicalSigns::class);
                    $lesionIds = $this->resolveMetaTermIds((array) request('human_lesions_id', []), Lesions::class);

                    $metaHuman = MetaHuman::create([
                        'studies_id' => request('studies_id'),
                        'sex' => request('sex'),
                        'age_group' => request('age_group'),
                        'job' => request('job'),
                        'habitat' => request('habitat'),
                        'location' => request('location'),
                        'countries_id' => $countries_id,
                        'date_sampling' => request('date_sampling'),
                        'sample_types_id' => $sample_types_id,
                        'pathogens_id' => request('pathogens_id'),
                        'techniques_id' => $techniques_id,
                        'tested_n' => request('tested_n'),
                        'pos_n' => request('pos_n'),
                        'projects_id' => $projectId,
                        'people_id' => $reviewerPeopleId,
                    ]);
                    $metaHuman->risk_factors()->sync($riskFactorIds);
                    $metaHuman->clinical_signs()->sync($clinicalSignIds);
                    $metaHuman->lesions()->sync($lesionIds);
                    SubProjectFlag::assign($metaHuman, $selectedSubProjectId);
                    break;

                case 'MetaParasite':
                    $parasite_sample_types_id = $this->service->check_or_create(
                        ParasiteSampleTypes::class,
                        ['name' => request('parasite_sample_types_id')],
                    );

                    $metaParasite = MetaParasite::create([
                        'studies_id' => request('studies_id'),
                        'parasite_species_id' => request('parasite_species_id'),
                        'sex' => request('sex'),
                        'stage' => request('stage'),
                        'location' => request('location'),
                        'countries_id' => $countries_id,
                        'date_sampling' => request('date_sampling'),
                        'parasite_sample_types_id' => $parasite_sample_types_id,
                        'pathogens_id' => request('pathogens_id'),
                        'techniques_id' => $techniques_id,
                        'tested_n' => request('tested_n'),
                        'pos_n' => request('pos_n'),
                        'projects_id' => $projectId,
                        'people_id' => $reviewerPeopleId,
                    ]);
                    $metaParasite->risk_factors()->sync($riskFactorIds);
                    SubProjectFlag::assign($metaParasite, $selectedSubProjectId);
                    break;

                case 'MetaEnvironment':
                    $environment_sample_types_id = $this->service->check_or_create(
                        EnvironmentSampleTypes::class,
                        ['name' => request('environment_sample_types_id')],
                        ['category' => request('environment_sample_category')],
                    );

                    $metaEnvironment = MetaEnvironment::create([
                        'studies_id' => request('studies_id'),
                        'environment_sample_types_id' => $environment_sample_types_id,
                        'location' => request('location'),
                        'countries_id' => $countries_id,
                        'date_sampling' => request('date_sampling'),
                        'pathogens_id' => request('pathogens_id'),
                        'techniques_id' => $techniques_id,
                        'tested_n' => request('tested_n'),
                        'pos_n' => request('pos_n'),
                        'projects_id' => $projectId,
                        'people_id' => $reviewerPeopleId,
                    ]);
                    $metaEnvironment->risk_factors()->sync($riskFactorIds);
                    SubProjectFlag::assign($metaEnvironment, $selectedSubProjectId);
                    break;

                default:
                    throw new \Exception('Invalid model type');
            }

            session()->flash('success', 'Literature data registered successfully!');

            // Get the authenticated user
            $user = Auth::user();

            $metaListLink = '/meta/list/animal';

            // Create notification
            NotificationController::create(
                'literature_created',
                'New Literature Data',
                $user->people->first_name.' extracted data from literature.',
                $metaListLink,
                $projectId
            );

            return redirect()->back();
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());

            return back()->withInput();
        }
    }

    private function resolveRegistrarPeopleId(string $requestKey): ?int
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        $projectId = session('selected_project_id');
        if (ProjectPermission::canAssignRegistrar($user, (int) $projectId)) {
            return request($requestKey) ? (int) request($requestKey) : null;
        }

        return ProjectPermission::currentRegistrarPeopleId($user);
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, int>
     */
    private function resolveMetaTermIds(array $values, string $modelClass): array
    {
        return collect($values)
            ->map(static fn ($value): string => is_string($value) ? trim($value) : '')
            ->filter()
            ->unique(static fn (string $value): string => mb_strtolower($value))
            ->map(fn (string $value): int => (int) $this->service->check_or_create($modelClass, ['name' => $value]))
            ->filter(static fn (int $id): bool => $id > 0)
            ->values()
            ->all();
    }

    private function sampleTypeExists(string $sampleTypeName): bool
    {
        return SampleTypes::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($sampleTypeName))])
            ->exists();
    }

    /**
     * @return array<string, string>
     */
    private function sampleTypeCategoryAttributes(string $category): array
    {
        $normalizedCategory = trim($category);
        if (! in_array($normalizedCategory, ['host_derived', 'non_host_derived'], true)) {
            return [];
        }

        return ['category' => $normalizedCategory];
    }
}
