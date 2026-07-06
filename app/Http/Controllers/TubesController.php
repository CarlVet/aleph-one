<?php

namespace App\Http\Controllers;

use App\Models\AnimalSamples;
use App\Models\Boxes;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\Projects;
use App\Models\TubePositions;
use App\Models\Tubes;
use App\Services\AnimalSamplesService;
use App\Services\BoxesService;
use App\Services\FieldSamplesService;
use App\Services\TubesService;
use App\Support\LookupTableData;
use App\Support\ProjectPermission;
use App\Support\SubProjectFlag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TubesController extends Controller
{
    protected $animalSamplesService;

    protected $fieldSamplesService;

    protected $tubesService;

    protected $boxesService;

    public function __construct(AnimalSamplesService $animalSamplesService, FieldSamplesService $fieldSamplesService, TubesService $tubesService, BoxesService $boxesService)
    {
        $this->animalSamplesService = $animalSamplesService;
        $this->fieldSamplesService = $fieldSamplesService;
        $this->tubesService = $tubesService;
        $this->boxesService = $boxesService;
    }

    public function create_positions()
    {
        $projectId = (int) session('selected_project_id');
        $user = Auth::user();
        $boxesData = $this->boxesService->assign();

        // Add dynamic content type to each box
        $boxes = $boxesData['boxes'];
        $dynamicTypesByBoxId = $this->dynamicContentTypesByBoxId($boxes->pluck('id'));
        foreach ($boxes as $box) {
            $box->dynamic_content_type = $dynamicTypesByBoxId[$box->id] ?? 'Empty';
        }

        $selected_human_tubes = $this->selectedTubesFromOldInput('human_tube_id');
        $selected_animal_tubes = $this->selectedTubesFromOldInput('animal_tube_id');
        $selected_environment_tubes = $this->selectedTubesFromOldInput('environment_tube_id');
        $selected_parasite_tubes = $this->selectedTubesFromOldInput('parasite_tube_id');
        $selected_nucleic_tubes = $this->selectedTubesFromOldInput('nucleic_tube_id');
        $selected_culture_tubes = $this->selectedTubesFromOldInput('culture_tube_id');
        $selected_pool_tubes = $this->selectedTubesFromOldInput('pool_tube_id');

        $data = [
            'selected_human_tubes' => $selected_human_tubes,
            'selected_animal_tubes' => $selected_animal_tubes,
            'selected_environment_tubes' => $selected_environment_tubes,
            'selected_parasite_tubes' => $selected_parasite_tubes,
            'selected_nucleic_tubes' => $selected_nucleic_tubes,
            'selected_culture_tubes' => $selected_culture_tubes,
            'selected_pool_tubes' => $selected_pool_tubes,
            'boxes' => $boxes,
            'box_lookup_rows' => LookupTableData::boxes($boxes),
        ];

        $defaultMovementReasons = [
            'Sample reorganization',
            'Temperature condition change',
            'Damaged box',
            'Accidental fall of box',
            'Storage optimization',
            'Correct misplacement',
        ];

        $movementReasonsDataset = TubePositions::query()
            ->join('tubes', 'tube_positions.tubes_id', '=', 'tubes.id')
            ->where('tubes.projects_id', session('selected_project_id'))
            ->whereNotNull('tube_positions.reason')
            ->distinct()
            ->orderBy('tube_positions.reason')
            ->pluck('tube_positions.reason')
            ->filter(fn ($v) => filled($v))
            ->values()
            ->all();

        $data['movement_reason_options'] = array_values(array_unique(array_merge($defaultMovementReasons, $movementReasonsDataset)));
        $data['animal_boxes'] = $boxesData['animal_boxes'];
        $data['parasite_boxes'] = $boxesData['parasite_boxes'];
        $data['nucleic_boxes'] = $boxesData['nucleic_boxes'];
        $data['locations'] = $boxesData['locations'];
        $data['labs'] = $boxesData['labs'];
        $data['people'] = $boxesData['people'];
        $data['can_assign_registrar'] = $user ? ProjectPermission::canAssignRegistrar($user, $projectId) : false;
        $data['locked_registrar_people_id'] = $user ? ProjectPermission::currentRegistrarPeopleId($user) : null;
        $data['sub_project_options'] = SubProjectFlag::optionsForUser($user, $projectId);

        return view('bank.tubes.create', $data);
    }

    private function selectedTubesFromOldInput(string $key): Collection
    {
        $ids = array_values(array_filter((array) old($key, [])));

        if (! $ids) {
            return collect();
        }

        return Tubes::query()
            ->whereIn('id', $ids)
            ->get(['id', 'code']);
    }

    public function getDynamicContentType($boxId)
    {
        $types = collect($this->dynamicTubeContentTypesByBoxId(collect([(int) $boxId])))
            ->get((int) $boxId, []);

        if (count($types) === 0) {
            return 'Empty';
        }

        if (count($types) > 1) {
            return 'Miscellaneous';
        }

        return $this->labelForTubeContentType($types[0]);
    }

    public function latestBoxTubePositions(Request $request, Boxes $box): JsonResponse
    {
        $projectId = (int) session('selected_project_id');
        if ((int) $box->projects_id !== $projectId) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $latestByTube = TubePositions::query()
            ->select('tubes_id')
            ->selectRaw('MAX(id) as latest_id')
            ->groupBy('tubes_id');

        $rows = TubePositions::query()
            ->joinSub($latestByTube, 'latest_by_tube', function ($join): void {
                $join->on('tube_positions.tubes_id', '=', 'latest_by_tube.tubes_id')
                    ->on('tube_positions.id', '=', 'latest_by_tube.latest_id');
            })
            ->where('tube_positions.boxes_id', $box->id)
            ->join('tubes', 'tube_positions.tubes_id', '=', 'tubes.id')
            ->select([
                'tube_positions.position_x',
                'tube_positions.position_y',
                'tubes.code as tube_code',
                'tubes.alias_code as tube_alias_code',
                'tubes.tubes_content_type',
            ])
            ->get();

        return response()->json([
            'box' => [
                'id' => $box->id,
                'code' => $box->code,
                'name' => $box->name,
                'n_rows' => $box->n_rows,
                'n_columns' => $box->n_columns,
            ],
            'occupied' => $rows,
        ]);
    }

    private function dynamicContentTypesByBoxId(Collection $boxIds): array
    {
        $typesByBoxId = $this->dynamicTubeContentTypesByBoxId($boxIds);

        $labelsByBoxId = [];
        foreach ($boxIds as $boxId) {
            $types = $typesByBoxId[(int) $boxId] ?? [];
            if (count($types) === 0) {
                $labelsByBoxId[(int) $boxId] = 'Empty';

                continue;
            }
            if (count($types) > 1) {
                $labelsByBoxId[(int) $boxId] = 'Miscellaneous';

                continue;
            }

            $labelsByBoxId[(int) $boxId] = $this->labelForTubeContentType($types[0]);
        }

        return $labelsByBoxId;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function dynamicTubeContentTypesByBoxId(Collection $boxIds): array
    {
        if ($boxIds->isEmpty()) {
            return [];
        }

        $latestByTube = TubePositions::query()
            ->select('tubes_id')
            ->selectRaw('MAX(id) as latest_id')
            ->groupBy('tubes_id');

        $rows = TubePositions::query()
            ->joinSub($latestByTube, 'latest_by_tube', function ($join): void {
                $join->on('tube_positions.tubes_id', '=', 'latest_by_tube.tubes_id')
                    ->on('tube_positions.id', '=', 'latest_by_tube.latest_id');
            })
            ->whereIn('tube_positions.boxes_id', $boxIds->map(fn ($id) => (int) $id)->all())
            ->join('tubes', 'tube_positions.tubes_id', '=', 'tubes.id')
            ->select([
                'tube_positions.boxes_id',
                'tubes.tubes_content_type',
            ])
            ->get();

        return $rows
            ->groupBy('boxes_id')
            ->map(fn (Collection $group) => $group->pluck('tubes_content_type')->unique()->values()->all())
            ->all();
    }

    private function labelForTubeContentType(string $type): string
    {
        return match ($type) {
            HumanSamples::class => 'Human samples',
            AnimalSamples::class => 'Animal samples',
            EnvironmentSamples::class => 'Environmental samples',
            ParasiteSamples::class => 'Parasite samples',
            NucleicAcids::class => 'Nucleic acids',
            Cultures::class => 'Cultures',
            Pools::class => 'Pools',
            default => 'Unknown',
        };
    }

    public function create_animals()
    {
        return view('samples.animals.process', $this->animalSamplesService->assign());
    }

    public function create_field_processing()
    {
        $projectId = (int) session('selected_project_id');

        return view('samples.process', array_merge(
            $this->fieldSamplesService->dataForFieldProcessingForm(),
            [
                'sub_project_options' => SubProjectFlag::optionsForUser(Auth::user(), $projectId),
            ]
        ));
    }

    public function store_positions()
    {
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);
        $project_code = $project->code;

        $rules = [
            'box' => 'required|exists:boxes,id',
            'date' => 'required|date',
            'x_position' => 'required|integer|min:1',
            'y_position' => 'required|integer|min:1',
            'sub_project_id' => 'nullable|integer|exists:sub_projects,id',
        ];

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {
            $box = Boxes::findOrFail(request('box'));
            $startX = request('x_position');
            $startY = request('y_position');
            $date = request('date');
            $moverPeopleId = $this->resolveRegistrarPeopleId('mover');
            $selectedSubProjectId = request('sub_project_id') ? (int) request('sub_project_id') : null;
            if (! SubProjectFlag::isSelectableByUser(Auth::user(), (int) $projectId, $selectedSubProjectId)) {
                session()->flash('error', 'Selected sub-project is not allowed for your user.');

                return back()->withInput();
            }

            // Get all selected tubes
            $tubeIds = [];
            if (request('human_tube_id')) {
                $tubeIds = array_merge($tubeIds, request('human_tube_id'));
            }
            if (request('animal_tube_id')) {
                $tubeIds = array_merge($tubeIds, request('animal_tube_id'));
            }
            if (request('environment_tube_id')) {
                $tubeIds = array_merge($tubeIds, request('environment_tube_id'));
            }
            if (request('parasite_tube_id')) {
                $tubeIds = array_merge($tubeIds, request('parasite_tube_id'));
            }
            if (request('nucleic_tube_id')) {
                $tubeIds = array_merge($tubeIds, request('nucleic_tube_id'));
            }
            if (request('culture_tube_id')) {
                $tubeIds = array_merge($tubeIds, request('culture_tube_id'));
            }
            if (request('pool_tube_id')) {
                $tubeIds = array_merge($tubeIds, request('pool_tube_id'));
            }
            if (request('experiment_tube_id')) {
                $tubeIds = array_merge($tubeIds, request('experiment_tube_id'));
            }

            // Register positions for each tube
            // Build current occupancy map (latest position per tube in this box)
            $latestByTube = TubePositions::query()
                ->select('tubes_id')
                ->selectRaw('MAX(id) as latest_id')
                ->groupBy('tubes_id');

            $currentRows = TubePositions::query()
                ->joinSub($latestByTube, 'latest_by_tube', function ($join): void {
                    $join->on('tube_positions.tubes_id', '=', 'latest_by_tube.tubes_id')
                        ->on('tube_positions.id', '=', 'latest_by_tube.latest_id');
                })
                ->where('tube_positions.boxes_id', $box->id)
                ->join('tubes', 'tube_positions.tubes_id', '=', 'tubes.id')
                ->select([
                    'tube_positions.tubes_id',
                    'tube_positions.position_x',
                    'tube_positions.position_y',
                    'tubes.code as tube_code',
                ])
                ->get();

            $occupiedByPos = [];
            foreach ($currentRows as $row) {
                $x = (int) $row->position_x;
                $y = (int) $row->position_y;
                if ($x < 1 || $y < 1) {
                    continue;
                }
                $occupiedByPos["{$x}-{$y}"] = [
                    'tubes_id' => (int) $row->tubes_id,
                    'tube_code' => (string) $row->tube_code,
                ];
            }

            $tubeIdSet = array_fill_keys(array_map('intval', $tubeIds), true);

            // Desired placements for selected tubes (left-to-right, top-to-bottom)
            $placements = []; // key "x-y" => ['tubes_id' => int, 'tube_code' => string]
            $placementOrder = []; // ordered list of keys for later scans

            $selectedTubeCodes = Tubes::query()
                ->whereIn('id', array_keys($tubeIdSet))
                ->pluck('code', 'id')
                ->all();

            $currentX = (int) $startX;
            $currentY = (int) $startY;

            foreach (array_keys($tubeIdSet) as $tubeId) {
                if ($currentX > (int) $box->n_columns) {
                    $currentX = 1;
                    $currentY++;
                }
                if ($currentY > (int) $box->n_rows) {
                    session()->flash('error', 'Not enough free positions in the selected box for the chosen tubes.');

                    return back()->withInput();
                }

                $key = "{$currentX}-{$currentY}";
                $placements[$key] = [
                    'tubes_id' => (int) $tubeId,
                    'tube_code' => (string) ($selectedTubeCodes[$tubeId] ?? ''),
                ];
                $placementOrder[] = $key;

                $currentX++;
            }

            // Find displaced tubes: occupied at a placement position, not already being moved.
            $displaced = []; // displacedTubeId => ['old_code'=>, 'replaced_by'=>, 'from_key'=>]
            foreach ($placements as $posKey => $new) {
                $occ = $occupiedByPos[$posKey] ?? null;
                if (! $occ) {
                    continue;
                }
                $occId = (int) $occ['tubes_id'];
                if (isset($tubeIdSet[$occId])) {
                    continue;
                }
                $displaced[$occId] = [
                    'old_code' => (string) $occ['tube_code'],
                    'replaced_by' => (string) $new['tube_code'],
                    'from_key' => $posKey,
                ];
            }

            // Compute free positions for displacements (positions not occupied by remaining tubes and not in new placements).
            $reserved = array_fill_keys(array_keys($placements), true);
            foreach ($occupiedByPos as $posKey => $occ) {
                $occId = (int) $occ['tubes_id'];
                if (isset($tubeIdSet[$occId])) {
                    continue; // being moved anyway
                }
                if (isset($displaced[$occId])) {
                    continue; // will be moved
                }
                $reserved[$posKey] = true; // stays
            }

            $freeKeys = [];
            for ($y = 1; $y <= (int) $box->n_rows; $y++) {
                for ($x = 1; $x <= (int) $box->n_columns; $x++) {
                    $k = "{$x}-{$y}";
                    if (! isset($reserved[$k])) {
                        $freeKeys[] = $k;
                    }
                }
            }

            if (count($displaced) > 0 && count($freeKeys) < count($displaced)) {
                session()->flash('error', 'This box is full. There are no free positions to move displaced tubes into.');

                return back()->withInput();
            }

            // First, move displaced tubes into free positions.
            $freeIndex = 0;
            foreach ($displaced as $displacedTubeId => $meta) {
                $targetKey = $freeKeys[$freeIndex++] ?? null;
                if (! $targetKey) {
                    continue;
                }
                [$tx, $ty] = array_map('intval', explode('-', $targetKey));

                $displacedPosition = TubePositions::create([
                    'tubes_id' => (int) $displacedTubeId,
                    'boxes_id' => $box->id,
                    'position_x' => $tx,
                    'position_y' => $ty,
                    'date_moved' => $date,
                    'people_id' => $moverPeopleId,
                    'reason' => trim((string) request('reason')).' (auto-moved: replaced by '.$meta['replaced_by'].')',
                ]);
                SubProjectFlag::assign($displacedPosition, $selectedSubProjectId);
            }

            // Then, place selected tubes (overriding any previous occupant).
            foreach ($placements as $posKey => $new) {
                [$px, $py] = array_map('intval', explode('-', $posKey));

                $newPosition = TubePositions::create([
                    'tubes_id' => (int) $new['tubes_id'],
                    'boxes_id' => $box->id,
                    'position_x' => $px,
                    'position_y' => $py,
                    'date_moved' => $date,
                    'people_id' => $moverPeopleId,
                    'reason' => request('reason'),
                ]);
                SubProjectFlag::assign($newPosition, $selectedSubProjectId);
            }

            Boxes::where('id', $box->id)->update([
                'content_type' => $this->getDynamicContentType($box->id),
            ]);

            session()->flash('success', 'Tube positions updated successfully!');

            $count = count($tubeIds);

            $user = Auth::user();

            NotificationController::create(
                'tube_moved',
                'Tubes Moved',
                $user->people->first_name.' moved '.$count.' tubes to box '.$box->code,
                '/bank/tubes/list',
                $projectId
            );

            return back();
        } catch (\Exception $e) {
            session()->flash('error', 'An unexpected error occurred. Please try again.');

            return back()->withInput();
        }
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

    public function store_animals()
    {
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);

        $rules = [
            'sample_select' => 'required|array|min:1',
            'sample_select.*' => 'required|exists:animal_samples,id',
            'tube_type' => 'required|string|max:255',
            'purpose' => 'required|string|max:255',
            'preservant' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'amount_unit' => 'nullable|string|max:50',
            'aliquots' => 'required|integer|min:1|max:20',
        ];

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Processing failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {
            $sampleIds = request('sample_select');
            $aliquots = request('aliquots');
            $tubeType = request('tube_type');
            $purpose = request('purpose');
            $preservant = request('preservant');
            $amount = request('amount');
            $amountUnit = request('amount_unit');
            $createdTubes = 0;

            foreach ($sampleIds as $sampleId) {
                // Verify the sample belongs to the current project
                $animalSample = AnimalSamples::where('id', $sampleId)
                    ->where('projects_id', $projectId)
                    ->first();

                if (! $animalSample) {
                    session()->flash('error', 'One or more selected samples do not belong to the current project.');

                    return back()->withInput();
                }

                // Get existing tube codes for this sample to generate sequential numbers
                $existingTubeCodes = Tubes::where('tubes_content_id', $sampleId)
                    ->where('tubes_content_type', 'App\\Models\\AnimalSamples')
                    ->pluck('code')
                    ->toArray();

                // Create tubes for this sample
                for ($i = 0; $i < $aliquots; $i++) {
                    // Generate sequential number for this tube
                    $tubeNumber = count($existingTubeCodes) + $i + 1;
                    $tubeCode = $animalSample->code.'-'.$tubeNumber;

                    // Ensure the tube code is unique across all tubes
                    $counter = 1;
                    $originalTubeCode = $tubeCode;
                    while (Tubes::where('code', $tubeCode)->exists()) {
                        $tubeCode = $originalTubeCode.'-'.$counter;
                        $counter++;
                    }

                    Tubes::create([
                        'code' => $tubeCode,
                        'tubes_content_id' => $sampleId,
                        'tubes_content_type' => 'App\\Models\\AnimalSamples',
                        'tube_type' => $tubeType,
                        'preservant' => $preservant,
                        'purpose' => $purpose,
                        'amount' => $amount,
                        'amount_unit' => $amountUnit,
                        'date_processed' => now(),
                        'projects_id' => $projectId,
                    ]);
                    $createdTubes++;
                }

                // Mark the sample as processed
                $animalSample->update(['processed' => true]);
            }

            // Flash success message to the session
            session()->flash('success', "Successfully processed {$aliquots} aliquot(s) for ".count($sampleIds)." sample(s). Total tubes created: {$createdTubes}");

            // Get the authenticated user
            $user = Auth::user();

            // Create notification
            NotificationController::create(
                'animal_sample_processed',
                'Animal Samples Processed',
                $user->people->first_name.' processed '.count($sampleIds).' animal sample(s) into '.$createdTubes.' tube(s)',
                '/samples/animals/list',
                $projectId
            );

            return back();
        } catch (\Exception $e) {
            Log::error('Error processing animal samples: '.$e->getMessage(), [
                'sample_ids' => request('sample_select'),
                'aliquots' => request('aliquots'),
                'tube_type' => request('tube_type'),
                'purpose' => request('purpose'),
                'project_id' => $projectId,
                'user_id' => Auth::id(),
            ]);

            session()->flash('error', 'An unexpected error occurred during processing. Please try again.');

            return back()->withInput();
        }
    }

    public function store_field_processing()
    {
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);

        $rules = [
            'sample_type' => 'required|in:human,animal,environment,parasite,nucleic,culture,pool',
            'tube_type' => 'required|string|max:255',
            'purpose' => 'required|string|max:255',
            'preservant' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'amount_unit' => 'nullable|string|max:50',
            'date_processed' => 'required|date',
            'aliquots' => 'required|integer|min:1|max:50',
            'is_historical' => 'required|in:0,1',
            'alias_code_assignments' => 'required_if:is_historical,1|array',
            'alias_code_assignments.*' => 'nullable|string|max:255',
            'sub_project_id' => 'nullable|integer|exists:sub_projects,id',
        ];

        // Add validation rules for sample selection based on sample type
        $sampleType = request('sample_type');
        if ($sampleType === 'human') {
            $rules['human_sample_select'] = 'required|array|min:1';
            $rules['human_sample_select.*'] = 'required|integer';
        } elseif ($sampleType === 'animal') {
            $rules['animal_sample_select'] = 'required|array|min:1';
            $rules['animal_sample_select.*'] = 'required|integer';
        } elseif ($sampleType === 'environment') {
            $rules['environment_sample_select'] = 'required|array|min:1';
            $rules['environment_sample_select.*'] = 'required|integer';
        } elseif ($sampleType === 'parasite') {
            $rules['parasite_sample_select'] = 'required|array|min:1';
            $rules['parasite_sample_select.*'] = 'required|integer';
        } elseif ($sampleType === 'nucleic') {
            $rules['nucleic_acid_select'] = 'required|array|min:1';
            $rules['nucleic_acid_select.*'] = 'required|integer';
        } elseif ($sampleType === 'culture') {
            $rules['culture_select'] = 'required|array|min:1';
            $rules['culture_select.*'] = 'required|integer';
        } elseif ($sampleType === 'pool') {
            $rules['pool_select'] = 'required|array|min:1';
            $rules['pool_select.*'] = 'required|integer';
        }

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Processing failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {
            $sampleType = request('sample_type');

            // Get sample IDs based on sample type
            $sampleIds = [];
            if ($sampleType === 'human') {
                $sampleIds = request('human_sample_select', []);
            } elseif ($sampleType === 'animal') {
                $sampleIds = request('animal_sample_select', []);
            } elseif ($sampleType === 'environment') {
                $sampleIds = request('environment_sample_select', []);
            } elseif ($sampleType === 'parasite') {
                $sampleIds = request('parasite_sample_select', []);
            } elseif ($sampleType === 'nucleic') {
                $sampleIds = request('nucleic_acid_select', []);
            } elseif ($sampleType === 'culture') {
                $sampleIds = request('culture_select', []);
            } elseif ($sampleType === 'pool') {
                $sampleIds = request('pool_select', []);
            }

            $aliquots = request('aliquots');
            $tubeType = request('tube_type');
            $purpose = request('purpose');
            $preservant = request('preservant');
            $amount = request('amount');
            $amountUnit = request('amount_unit');
            $dateProcessed = request('date_processed');
            $isHistorical = request('is_historical');
            $aliasCodeAssignments = request('alias_code_assignments');
            $selectedSubProjectId = request('sub_project_id') ? (int) request('sub_project_id') : null;
            $createdTubes = 0;

            if (! SubProjectFlag::isSelectableByUser(Auth::user(), (int) $projectId, $selectedSubProjectId)) {
                session()->flash('error', 'Selected sub-project is not allowed for your user.');

                return back()->withInput();
            }

            // Determine model and content type
            $modelMap = [
                'animal' => ['model' => AnimalSamples::class, 'type' => 'App\\Models\\AnimalSamples'],
                'human' => ['model' => HumanSamples::class, 'type' => 'App\\Models\\HumanSamples'],
                'environment' => ['model' => EnvironmentSamples::class, 'type' => 'App\\Models\\EnvironmentSamples'],
                'parasite' => ['model' => ParasiteSamples::class, 'type' => 'App\\Models\\ParasiteSamples'],
                'nucleic' => ['model' => NucleicAcids::class, 'type' => 'App\\Models\\NucleicAcids'],
                'culture' => ['model' => Cultures::class, 'type' => 'App\\Models\\Cultures'],
                'pool' => ['model' => Pools::class, 'type' => 'App\\Models\\Pools'],
            ];
            $modelClass = $modelMap[$sampleType]['model'];
            $contentType = $modelMap[$sampleType]['type'];

            foreach ($sampleIds as $sampleId) {
                // Verify the sample belongs to the current project
                $sample = $modelClass::where('id', $sampleId)
                    ->where('projects_id', $projectId)
                    ->first();

                if (! $sample) {
                    session()->flash('error', 'One or more selected samples do not belong to the current project.');

                    return back()->withInput();
                }

                // Get existing tube codes for this sample to generate sequential numbers
                $existingTubeCodes = Tubes::where('tubes_content_id', $sampleId)
                    ->where('tubes_content_type', $contentType)
                    ->pluck('code')
                    ->toArray();

                for ($i = 0; $i < $aliquots; $i++) {
                    // Generate tube code
                    $tubeNumber = count($existingTubeCodes) + $i + 1;
                    $tubeCode = $sample->code.'-'.$tubeNumber;

                    // Ensure the tube code is unique across all tubes
                    $counter = 1;
                    $originalTubeCode = $tubeCode;
                    while (Tubes::where('code', $tubeCode)->exists()) {
                        $tubeCode = $originalTubeCode.'-'.$counter;
                        $counter++;
                    }

                    // Calculate alias code index for this specific sample and aliquot
                    $sampleIndex = array_search($sampleId, $sampleIds);
                    $aliasCodeIndex = ($sampleIndex * $aliquots) + $i;
                    $aliasCode = null;
                    if ($isHistorical && $aliasCodeAssignments && isset($aliasCodeAssignments[$aliasCodeIndex])) {
                        $aliasCode = $aliasCodeAssignments[$aliasCodeIndex];
                    }

                    $createdTube = Tubes::create([
                        'code' => $tubeCode,
                        'alias_code' => $aliasCode,
                        'tubes_content_id' => $sampleId,
                        'tubes_content_type' => $contentType,
                        'tube_type' => $tubeType,
                        'preservant' => $preservant,
                        'purpose' => $purpose,
                        'amount' => $amount,
                        'amount_unit' => $amountUnit,
                        'date_processed' => $dateProcessed,
                        'projects_id' => $projectId,
                    ]);
                    SubProjectFlag::assign($createdTube, $selectedSubProjectId);
                    $createdTubes++;
                }

                if ($sampleType === 'human') {
                    HumanSamples::where('id', $sampleId)->update(['processed' => true]);
                } elseif ($sampleType === 'animal') {
                    AnimalSamples::where('id', $sampleId)->update(['processed' => true]);
                } elseif ($sampleType === 'environment') {
                    EnvironmentSamples::where('id', $sampleId)->update(['processed' => true]);
                }
            }

            session()->flash('success', "Successfully processed {$aliquots} aliquot(s) for ".count($sampleIds)." sample(s). Total tubes created: {$createdTubes}");

            $user = Auth::user();
            NotificationController::create(
                'field_sample_processed',
                'Field Samples Processed',
                $user->people->first_name.' processed '.count($sampleIds).' '.$sampleType.' sample(s) into '.$createdTubes.' tube(s)',
                '/bank/tubes/list',
                $projectId
            );

            return back();
        } catch (\Exception $e) {
            session()->flash('error', 'An unexpected error occurred during processing. Please try again.');

            return back()->withInput();
        }
    }
}
