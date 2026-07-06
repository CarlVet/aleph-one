<?php

namespace App\Http\Controllers;

use App\Enums\ExperimentPurpose;
use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pathogens;
use App\Models\PoolContents;
use App\Models\Pools;
use App\Models\Projects;
use App\Models\Protocols;
use App\Models\Tubes;
use App\Services\ExperimentsService;
use App\Support\ProjectPermission;
use App\Support\SubProjectFlag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ExperimentsController extends Controller
{
    protected $service;

    public function __construct(ExperimentsService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        $projectId = (int) session('selected_project_id');
        $user = Auth::user();
        $createData = $this->service->dataForCreate();

        $tableTubeOptions = Tubes::query()
            ->where('projects_id', $projectId)
            ->orderBy('code')
            ->get(['id', 'code', 'alias_code'])
            ->map(function (Tubes $tube): array {
                $alias = filled($tube->alias_code) ? ' ('.$tube->alias_code.')' : '';

                return [
                    'id' => (int) $tube->id,
                    'label' => (string) $tube->code.$alias,
                ];
            })
            ->values();

        return view('experiments.create', array_merge($createData, [
            'can_assign_registrar' => $user ? ProjectPermission::canAssignRegistrar($user, $projectId) : false,
            'locked_registrar_people_id' => $user ? ProjectPermission::currentRegistrarPeopleId($user) : null,
            'sub_project_options' => SubProjectFlag::optionsForUser($user, $projectId),
            'table_tube_options' => $tableTubeOptions,
        ]));
    }

    public function suitability(Request $request): JsonResponse
    {
        $projectId = (int) session('selected_project_id');

        $protocolName = trim((string) $request->input('protocol', ''));
        $techniqueLabel = trim((string) $request->input('technique', ''));
        $tubeBadgeDisplay = trim((string) $request->input('tube_badge_display', 'tube'));
        $tubeIds = array_values(array_filter((array) $request->input('tube_ids', [])));

        if ($protocolName === '' || $techniqueLabel === '' || empty($tubeIds)) {
            return response()->json(['ok' => true, 'warnings' => []]);
        }

        $protocol = Protocols::query()
            ->where('name', $protocolName)
            ->with('techniques')
            ->first();

        if (! $protocol || ! $protocol->techniques) {
            return response()->json(['ok' => true, 'warnings' => []]);
        }

        $technique = $protocol->techniques;
        $label = strtolower(trim((string) ($technique->type ?: $technique->name ?: $techniqueLabel)));

        $group = null;
        if (str_contains($label, 'nucleic') && str_contains($label, 'detection')) {
            $group = 'nucleic_detection';
        } elseif (str_contains($label, 'antibody') && str_contains($label, 'detection')) {
            $group = 'antibody_detection';
        } elseif (str_contains($label, 'parasitological')) {
            $group = 'parasitological';
        } elseif (str_contains($label, 'microbiological')) {
            $group = 'microbiological';
        }

        if (! $group) {
            return response()->json(['ok' => true, 'warnings' => []]);
        }

        $tubes = Tubes::query()
            ->where('projects_id', $projectId)
            ->whereIn('id', $tubeIds)
            ->get(['id', 'code', 'alias_code', 'tubes_content_type', 'tubes_content_id']);

        if ($tubes->isEmpty()) {
            return response()->json(['ok' => true, 'warnings' => []]);
        }

        $serumNames = ['serum'];

        $humanIds = $tubes->where('tubes_content_type', HumanSamples::class)->pluck('tubes_content_id')->unique()->values();
        $animalIds = $tubes->where('tubes_content_type', AnimalSamples::class)->pluck('tubes_content_id')->unique()->values();
        $poolIds = $tubes->where('tubes_content_type', Pools::class)->pluck('tubes_content_id')->unique()->values();

        $humanSampleTypeById = $humanIds->isNotEmpty()
            ? HumanSamples::query()
                ->whereIn('id', $humanIds)
                ->with('sample_types:id,name')
                ->get(['id', 'sample_types_id'])
                ->mapWithKeys(fn (HumanSamples $s) => [$s->id => strtolower((string) optional($s->sample_types)->name)])
                ->all()
            : [];

        $animalSampleTypeById = $animalIds->isNotEmpty()
            ? AnimalSamples::query()
                ->whereIn('id', $animalIds)
                ->with('sample_types:id,name')
                ->get(['id', 'sample_types_id'])
                ->mapWithKeys(fn (AnimalSamples $s) => [$s->id => strtolower((string) optional($s->sample_types)->name)])
                ->all()
            : [];

        $poolContents = $poolIds->isNotEmpty()
            ? PoolContents::query()
                ->whereIn('pools_id', $poolIds)
                ->get(['pools_id', 'samples_type', 'samples_id'])
            : collect();

        $poolHumanIds = $poolContents
            ->where('samples_type', HumanSamples::class)
            ->pluck('samples_id')
            ->unique()
            ->values();
        $poolAnimalIds = $poolContents
            ->where('samples_type', AnimalSamples::class)
            ->pluck('samples_id')
            ->unique()
            ->values();

        if ($poolHumanIds->isNotEmpty()) {
            $humanSampleTypeById = array_replace($humanSampleTypeById, HumanSamples::query()
                ->whereIn('id', $poolHumanIds)
                ->with('sample_types:id,name')
                ->get(['id', 'sample_types_id'])
                ->mapWithKeys(fn (HumanSamples $s) => [$s->id => strtolower((string) optional($s->sample_types)->name)])
                ->all());
        }
        if ($poolAnimalIds->isNotEmpty()) {
            $animalSampleTypeById = array_replace($animalSampleTypeById, AnimalSamples::query()
                ->whereIn('id', $poolAnimalIds)
                ->with('sample_types:id,name')
                ->get(['id', 'sample_types_id'])
                ->mapWithKeys(fn (AnimalSamples $s) => [$s->id => strtolower((string) optional($s->sample_types)->name)])
                ->all());
        }

        $warnings = [];

        foreach ($tubes as $tube) {
            $contentType = (string) $tube->tubes_content_type;
            $tubeLabel = $tubeBadgeDisplay === 'alias' && filled($tube->alias_code)
                ? (string) $tube->alias_code
                : (string) $tube->code;

            if ($group === 'nucleic_detection') {
                if ($contentType === NucleicAcids::class) {
                    continue;
                }

                if ($contentType === Pools::class) {
                    $contents = $poolContents->where('pools_id', $tube->tubes_content_id);
                    $allNucleic = $contents->isNotEmpty() && $contents->every(fn ($c) => $c->samples_type === NucleicAcids::class);
                    if ($allNucleic) {
                        continue;
                    }

                    $warnings[] = "Tube {$tubeLabel}: pool is not exclusively composed of nucleic acids.";

                    continue;
                }

                $warnings[] = "Tube {$tubeLabel}: nucleic acid detection tests are intended for nucleic acids (or pools of nucleic acids).";

                continue;
            }

            if ($group === 'antibody_detection') {
                if ($contentType === HumanSamples::class) {
                    $sampleType = $humanSampleTypeById[(int) $tube->tubes_content_id] ?? null;
                    if ($sampleType && in_array($sampleType, $serumNames, true)) {
                        continue;
                    }
                    $warnings[] = "Tube {$tubeLabel}: antibody detection tests are intended for serum (human).";

                    continue;
                }

                if ($contentType === AnimalSamples::class) {
                    $sampleType = $animalSampleTypeById[(int) $tube->tubes_content_id] ?? null;
                    if ($sampleType && in_array($sampleType, $serumNames, true)) {
                        continue;
                    }
                    $warnings[] = "Tube {$tubeLabel}: antibody detection tests are intended for serum (animal).";

                    continue;
                }

                if ($contentType === Pools::class) {
                    $contents = $poolContents->where('pools_id', $tube->tubes_content_id);
                    $ok = $contents->isNotEmpty() && $contents->every(function ($c) use ($humanSampleTypeById, $animalSampleTypeById, $serumNames): bool {
                        if ($c->samples_type === HumanSamples::class) {
                            $st = $humanSampleTypeById[(int) $c->samples_id] ?? null;

                            return $st && in_array($st, $serumNames, true);
                        }
                        if ($c->samples_type === AnimalSamples::class) {
                            $st = $animalSampleTypeById[(int) $c->samples_id] ?? null;

                            return $st && in_array($st, $serumNames, true);
                        }

                        return false;
                    });
                    if ($ok) {
                        continue;
                    }

                    $warnings[] = "Tube {$tubeLabel}: antibody detection tests are intended for pools made exclusively of serum samples (human/animal).";

                    continue;
                }

                $warnings[] = "Tube {$tubeLabel}: antibody detection tests are intended for serum human/animal samples (or pools of serum).";

                continue;
            }

            if ($group === 'parasitological') {
                if ($contentType === ParasiteSamples::class) {
                    continue;
                }
                if ($contentType === Pools::class) {
                    $contents = $poolContents->where('pools_id', $tube->tubes_content_id);
                    $ok = $contents->isNotEmpty() && $contents->every(fn ($c) => $c->samples_type === ParasiteSamples::class);
                    if ($ok) {
                        continue;
                    }
                    $warnings[] = "Tube {$tubeLabel}: parasitological tests are intended for parasite samples (or pools of parasite samples).";

                    continue;
                }

                $warnings[] = "Tube {$tubeLabel}: parasitological tests are intended for parasite samples (or pools of parasite samples).";

                continue;
            }

            if ($group === 'microbiological') {
                if ($contentType === Cultures::class) {
                    continue;
                }

                $warnings[] = "Tube {$tubeLabel}: microbiological tests are intended for cultures.";

                continue;
            }
        }

        return response()->json([
            'ok' => true,
            'warnings' => array_values(array_unique($warnings)),
        ]);
    }

    public function store()
    {
        if ((string) request('register_mode') === 'table') {
            return $this->storeFromTable();
        }

        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);
        $project_code = $project->code;

        $rules = [
            'human_tube_id' => 'required_if:model,Human samples',
            'animal_tube_id' => 'required_if:model,Animal samples',
            'environment_tube_id' => 'required_if:model,Environment samples',
            'parasite_tube_id' => 'required_if:model,Parasite samples',
            'nucleic_tube_id' => 'required_if:model,Nucleic acids',
            'culture_tube_id' => 'required_if:model,Culture samples',
            'pool_tube_id' => 'required_if:model,Pool samples',
            'protocol' => 'string|max:100|required',
            'pathogen' => 'array|max:100|required',
            'outcome_qual' => 'string|max:100|required',
            'outcome_quant' => 'numeric',
            'purpose' => ['required', Rule::in(ExperimentPurpose::values())],
            'date' => 'required|date|before_or_equal:today',
            'lab' => 'string|max:100|required',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,tif,tiff,pdf|max:51200', // 50MB max
            'sub_project_id' => 'nullable|integer|exists:sub_projects,id',
        ];

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {

            $model = request('model');

            $protocols_id = $this->service->check_or_create(
                Protocols::class,
                ['name' => request('protocol')]
            );

            $lab_id = $this->service->check_or_create(
                Laboratories::class,
                ['name' => request('lab')]
            );

            $outcome_qual = request('outcome_qual');
            $scientistPeopleId = $this->resolveRegistrarPeopleId('scientist');
            $selectedSubProjectId = request('sub_project_id') ? (int) request('sub_project_id') : null;
            if (! SubProjectFlag::isSelectableByUser(Auth::user(), (int) $projectId, $selectedSubProjectId)) {
                session()->flash('error', 'Selected sub-project is not allowed for your user.');

                return back()->withInput();
            }

            if (in_array($outcome_qual, ['Strong positive', 'Positive'])) {
                $outcome_binary = 1;
            } elseif (in_array($outcome_qual, ['Suspect', 'Negative'])) {
                $outcome_binary = 0;
            } else {
                $outcome_binary = null; // Handle other cases as needed
            }

            // Determine the origin type and IDs based on the selected model
            $originType = null;
            $tubeIds = [];
            switch ($model) {
                case 'Human samples':
                    $originType = 'App\Models\HumanSamples';
                    $tubeIds = request('human_tube_id');
                    break;
                case 'Animal samples':
                    $originType = 'App\Models\AnimalSamples';
                    $tubeIds = request('animal_tube_id');
                    break;
                case 'Environmental samples':
                    $originType = 'App\Models\EnvironmentSamples';
                    $tubeIds = request('environment_tube_id');
                    break;
                case 'Parasite samples':
                    $originType = 'App\Models\ParasiteSamples';
                    $tubeIds = request('parasite_tube_id');
                    break;
                case 'Nucleic acids':
                    $originType = 'App\Models\NucleicAcids';
                    $tubeIds = request('nucleic_tube_id');
                    break;
                case 'Cultures':
                    $originType = 'App\Models\Cultures';
                    $tubeIds = request('culture_tube_id');
                    break;
                case 'Pools':
                    $originType = 'App\Models\Pools';
                    $tubeIds = request('pool_tube_id');
                    break;
            }

            foreach ($tubeIds as $tubeId) {

                $experiments_content_id = Tubes::where('id', $tubeId)->first()->tubes_content_id;

                foreach (request('pathogen') as $pathogen) {

                    $pathogens_id = $pathogen;

                    $project_code = Projects::where('id', $projectId)->first()->code;

                    $existingExCodes = Experiments::where('projects_id', $projectId)
                        ->where('code', 'like', $project_code.'-EX-%')
                        ->pluck('code');

                    $usedNumbers = $existingExCodes->map(function ($code) {
                        preg_match('/-EX-(\d+)$/', $code, $matches);

                        return isset($matches[1]) ? (int) $matches[1] : null;
                    })->filter()->sort()->values();

                    $newSerial = 1;
                    foreach ($usedNumbers as $num) {
                        if ($num != $newSerial) {
                            break;
                        }
                        $newSerial++;
                    }

                    $ex_code = $project_code.'-EX-'.$newSerial;

                    // Handle photo upload
                    $photo_path = null;
                    if (request()->hasFile('photo')) {
                        $photo = request()->file('photo');
                        $photo_path = $photo->storePublicly('experiments', ['disk' => 'local']);
                    }

                    $experiment = Experiments::create([
                        'code' => $ex_code,
                        'experiments_content_type' => $originType,
                        'experiments_content_id' => $experiments_content_id,
                        'protocols_id' => $protocols_id,
                        'pathogens_id' => $pathogens_id,
                        'outcome_discrete' => $outcome_qual,
                        'outcome_quant' => request('outcome_quant'),
                        'outcome_binary' => $outcome_binary,
                        'purpose' => request('purpose'),
                        'date_tested' => request('date'),
                        'people_id' => $scientistPeopleId,
                        'laboratories_id' => $lab_id,
                        'projects_id' => $projectId,
                        'photo_path' => $photo_path,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    SubProjectFlag::assign($experiment, $selectedSubProjectId);
                }
            }

            session()->flash('success', 'Experiment registered successfully!');

            $count = count($tubeIds) * count(request('pathogen'));

            $user = Auth::user();

            NotificationController::create(
                'experiment_created',
                'New Experiments',
                $user->people->first_name.' registered '.$count.' experiment'.($count > 1 ? 's.' : '.'),
                '/experiments/list',
                $projectId
            );

            return back();
        } catch (\Exception $e) {
            // Handle any exceptions during registration
            session()->flash('error', 'An error occurred: '.$e->getMessage());

            return back()->withInput();
        }
    }

    private function storeFromTable()
    {
        $projectId = (int) session('selected_project_id');
        $project = Projects::findOrFail($projectId);
        $projectCode = $project->code;
        $user = Auth::user();

        $rows = $this->normalizeTableRows((array) request('table_rows', []));
        $allowedOutcomeTypes = ['Qualitative only', 'Both qualitative and quantitative'];
        $allowedQualitativeOutcomes = ['Strong positive', 'Positive', 'Suspect', 'Negative', 'Inconclusive', 'Unsuccessful', 'To be repeated'];

        $validator = Validator::make(
            ['table_rows' => $rows, 'sub_project_id' => request('sub_project_id')],
            [
                'table_rows' => 'required|array|min:1',
                'table_rows.*.tube_id' => 'required|integer|exists:tubes,id',
                'table_rows.*.protocol_name' => 'required|string|max:255',
                'table_rows.*.pathogen' => 'required|string|max:255',
                'table_rows.*.outcome_type' => 'required|in:Qualitative only,Both qualitative and quantitative',
                'table_rows.*.outcome_qual' => 'required|in:Strong positive,Positive,Suspect,Negative,Inconclusive,Unsuccessful,To be repeated',
                'table_rows.*.outcome_quant' => 'nullable|numeric',
                'table_rows.*.purpose' => ['required', Rule::in(ExperimentPurpose::values())],
                'table_rows.*.date_tested' => 'required|date|before_or_equal:today',
                'table_rows.*.laboratory' => 'required|string|max:255',
                'table_rows.*.tested_by' => 'required|integer|exists:people,id',
                'sub_project_id' => 'nullable|integer|exists:sub_projects,id',
            ]
        );

        $validator->after(function ($validator) use ($rows, $projectId, $allowedOutcomeTypes, $allowedQualitativeOutcomes): void {
            foreach ($rows as $index => $row) {
                $tube = Tubes::query()
                    ->where('projects_id', $projectId)
                    ->find((int) Arr::get($row, 'tube_id'));

                if (! $tube) {
                    $validator->errors()->add("table_rows.$index.tube_id", 'Selected tube does not belong to the current project.');
                }

                $protocol = Protocols::query()
                    ->where('name', (string) Arr::get($row, 'protocol_name'))
                    ->with('pathogens:id,species')
                    ->first();

                if (! $protocol) {
                    $validator->errors()->add("table_rows.$index.protocol_name", 'Protocol not found. Create it first with the + button.');
                }

                $pathogen = Pathogens::query()
                    ->where('species', (string) Arr::get($row, 'pathogen'))
                    ->first();

                if (! $pathogen) {
                    $validator->errors()->add("table_rows.$index.pathogen", 'Pathogen not found. Create it first with the + button.');
                }

                if ($protocol && $pathogen && ! $protocol->pathogens->contains('id', $pathogen->id)) {
                    $validator->errors()->add("table_rows.$index.pathogen", 'Pathogen must already be associated with the selected protocol.');
                }

                $laboratory = Laboratories::query()
                    ->where('name', (string) Arr::get($row, 'laboratory'))
                    ->first();

                if (! $laboratory) {
                    $validator->errors()->add("table_rows.$index.laboratory", 'Laboratory not found. Create it first with the + button.');
                }

                if (! in_array((string) Arr::get($row, 'outcome_type'), $allowedOutcomeTypes, true)) {
                    $validator->errors()->add("table_rows.$index.outcome_type", 'Invalid outcome type.');
                }

                if (! in_array((string) Arr::get($row, 'outcome_qual'), $allowedQualitativeOutcomes, true)) {
                    $validator->errors()->add("table_rows.$index.outcome_qual", 'Invalid qualitative outcome.');
                }

                if (
                    (string) Arr::get($row, 'outcome_type') === 'Both qualitative and quantitative'
                    && ! filled(Arr::get($row, 'outcome_quant'))
                ) {
                    $validator->errors()->add("table_rows.$index.outcome_quant", 'Quantitative outcome is required when outcome type is both qualitative and quantitative.');
                }
            }
        });

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix table row errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        $selectedSubProjectId = request('sub_project_id') ? (int) request('sub_project_id') : null;
        if (! SubProjectFlag::isSelectableByUser($user, $projectId, $selectedSubProjectId)) {
            session()->flash('error', 'Selected sub-project is not allowed for your user.');

            return back()->withInput();
        }

        $experimentSerial = $this->nextSerialForProjectPattern(Experiments::class, $projectId, $projectCode.'-EX-');
        $createdExperiments = 0;

        DB::transaction(function () use ($rows, $projectId, $projectCode, $selectedSubProjectId, &$experimentSerial, &$createdExperiments): void {
            foreach ($rows as $row) {
                $tube = Tubes::query()->findOrFail((int) $row['tube_id']);
                $protocol = Protocols::query()->where('name', (string) $row['protocol_name'])->firstOrFail();
                $pathogen = Pathogens::query()->where('species', (string) $row['pathogen'])->firstOrFail();
                $laboratory = Laboratories::query()->where('name', (string) $row['laboratory'])->firstOrFail();
                $outcomeQual = (string) $row['outcome_qual'];
                $outcomeBinary = null;

                if (in_array($outcomeQual, ['Strong positive', 'Positive'], true)) {
                    $outcomeBinary = 1;
                } elseif (in_array($outcomeQual, ['Suspect', 'Negative'], true)) {
                    $outcomeBinary = 0;
                }

                $experiment = Experiments::create([
                    'code' => $projectCode.'-EX-'.$experimentSerial++,
                    'experiments_content_type' => (string) $tube->tubes_content_type,
                    'experiments_content_id' => (int) $tube->tubes_content_id,
                    'protocols_id' => (int) $protocol->id,
                    'pathogens_id' => (int) $pathogen->id,
                    'outcome_discrete' => $outcomeQual,
                    'outcome_quant' => (string) $row['outcome_type'] === 'Both qualitative and quantitative' && filled($row['outcome_quant'])
                        ? (float) $row['outcome_quant']
                        : null,
                    'outcome_binary' => $outcomeBinary,
                    'purpose' => (string) $row['purpose'],
                    'date_tested' => (string) $row['date_tested'],
                    'people_id' => (int) $row['tested_by'],
                    'laboratories_id' => (int) $laboratory->id,
                    'projects_id' => $projectId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                SubProjectFlag::assign($experiment, $selectedSubProjectId);
                $createdExperiments++;
            }
        });

        session()->flash('success', 'Experiments registered successfully!');

        NotificationController::create(
            'experiment_created',
            'New Experiments',
            $user->people->first_name.' registered '.$createdExperiments.' experiment'.($createdExperiments > 1 ? 's.' : '.'),
            '/experiments/list',
            $projectId
        );

        return back();
    }

    private function resolveRegistrarPeopleId(string $requestKey): ?int
    {
        $user = Auth::user();
        if (! $user || ! $user->people) {
            return request($requestKey) ? (int) request($requestKey) : null;
        }

        $projectId = (int) session('selected_project_id');
        if (! ProjectPermission::canAssignRegistrar($user, $projectId)) {
            return (int) $user->people->id;
        }

        return request($requestKey) ? (int) request($requestKey) : (int) $user->people->id;
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeTableRows(array $rows): array
    {
        return collect($rows)
            ->map(function ($row): array {
                $normalized = is_array($row) ? $row : [];

                return [
                    'tube_id' => trim((string) ($normalized['tube_id'] ?? '')),
                    'protocol_name' => trim((string) ($normalized['protocol_name'] ?? '')),
                    'pathogen' => trim((string) ($normalized['pathogen'] ?? '')),
                    'outcome_type' => trim((string) ($normalized['outcome_type'] ?? '')),
                    'outcome_qual' => trim((string) ($normalized['outcome_qual'] ?? '')),
                    'outcome_quant' => trim((string) ($normalized['outcome_quant'] ?? '')),
                    'purpose' => trim((string) ($normalized['purpose'] ?? '')),
                    'date_tested' => trim((string) ($normalized['date_tested'] ?? '')),
                    'laboratory' => trim((string) ($normalized['laboratory'] ?? '')),
                    'tested_by' => trim((string) ($normalized['tested_by'] ?? '')),
                ];
            })
            ->filter(function (array $row): bool {
                return collect($row)->contains(fn ($value) => $value !== '');
            })
            ->values()
            ->all();
    }

    private function nextSerialForProjectPattern(string $modelClass, int $projectId, string $prefix): int
    {
        $codes = $modelClass::query()
            ->where('projects_id', $projectId)
            ->where('code', 'like', $prefix.'%')
            ->pluck('code');

        $usedNumbers = $codes->map(function ($code) {
            preg_match('/(\d+)$/', (string) $code, $matches);

            return isset($matches[1]) ? (int) $matches[1] : null;
        })->filter()->sort()->values();

        $serial = 1;
        foreach ($usedNumbers as $num) {
            if ($num !== $serial) {
                break;
            }
            $serial++;
        }

        return $serial;
    }
}
