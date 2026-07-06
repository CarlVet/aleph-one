<?php

namespace App\Http\Controllers;

use App\Models\AnimalSamples;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\Parasites;
use App\Models\ParasiteSamples;
use App\Models\ParasiteSampleTypes;
use App\Models\ParasiteSpecies;
use App\Models\Pools;
use App\Models\Projects;
use App\Models\Tubes;
use App\Services\ParasiteSamplesService;
use App\Support\ParasiteObservationRecorder;
use App\Support\ParasiteSampleObservationRecorder;
use App\Support\ProjectPermission;
use App\Support\SubProjectFlag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ParasiteSamplesController extends Controller
{
    protected $service;

    public function __construct(ParasiteSamplesService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);
        $project_code = $project->code;

        // Get all existing parasite sample codes for this project
        $existingPsCodes = ParasiteSamples::where('projects_id', $projectId)
            ->where('code', 'like', $project_code.'-PS-%')
            ->pluck('code');

        $usedNumbers = $existingPsCodes->map(function ($code) {
            preg_match('/-PS-(\d+)$/', $code, $matches);

            return isset($matches[1]) ? (int) $matches[1] : null;
        })->filter()->sort()->values();

        // Generate the next 100 truly available PS codes (skip all used serials, not only leading block).
        $availableCodes = [];
        $usedPsSet = array_fill_keys($usedNumbers->all(), true);
        $nextPsNumber = 1;
        while (count($availableCodes) < 100) {
            if (! isset($usedPsSet[$nextPsNumber])) {
                $availableCodes[] = $project_code.'-PS-'.$nextPsNumber;
            }
            $nextPsNumber++;
        }

        // Generate a list of available pool codes (next 100)
        $existingPoolCodes = Pools::where('projects_id', $projectId)
            ->where('code', 'like', $project_code.'-PO-%')
            ->pluck('code');

        $usedPoolNumbers = $existingPoolCodes->map(function ($code) {
            preg_match('/-PO-(\d+)$/', $code, $matches);

            return isset($matches[1]) ? (int) $matches[1] : null;
        })->filter()->sort()->values();

        $nextPoolNumber = 1;
        foreach ($usedPoolNumbers as $num) {
            if ($num != $nextPoolNumber) {
                break;
            }
            $nextPoolNumber++;
        }

        $availablePoolCodes = [];
        $usedPoSet = array_fill_keys($usedPoolNumbers->all(), true);
        $candidatePoolNumber = $nextPoolNumber;
        while (count($availablePoolCodes) < 100) {
            if (! isset($usedPoSet[$candidatePoolNumber])) {
                $availablePoolCodes[] = $project_code.'-PO-'.$candidatePoolNumber;
            }
            $candidatePoolNumber++;
        }

        $viewData = $this->service->dataForCreate();
        $viewData['available_codes'] = $availableCodes;
        $viewData['available_pool_codes'] = $availablePoolCodes;
        $viewData['project_code'] = $project_code;
        $viewData['selected_human_samples'] = $this->selectedSamplesFromOldInput('human_sample_id', HumanSamples::class);
        $viewData['selected_animal_samples'] = $this->selectedSamplesFromOldInput('animal_sample_id', AnimalSamples::class);
        $viewData['selected_environment_samples'] = $this->selectedSamplesFromOldInput('environment_sample_id', EnvironmentSamples::class);
        $user = Auth::user();
        $viewData['can_assign_registrar'] = $user ? ProjectPermission::canAssignRegistrar($user, $projectId) : false;
        $viewData['locked_registrar_people_id'] = $user ? ProjectPermission::currentRegistrarPeopleId($user) : null;
        $viewData['sub_project_options'] = SubProjectFlag::optionsForUser($user, (int) $projectId);

        return view('samples.parasites.create', $viewData);
    }

    /**
     * @return Collection<int, Model>
     */
    private function selectedSamplesFromOldInput(string $key, string $modelClass)
    {
        $ids = array_values(array_filter((array) old($key, []), fn ($id) => is_numeric($id)));

        if (! $ids) {
            return collect([]);
        }

        return $modelClass::query()
            ->whereIn('id', $ids)
            ->select(['id', 'code'])
            ->orderBy('code')
            ->get();
    }

    public function store()
    {
        $rules = [
            'model' => 'required|in:Human samples,Animal samples,Environmental samples',
            'parasite_species' => 'required',
            'parasite_codes' => 'nullable|array',
            'tick_counts' => 'nullable|array',
            'date' => 'required|date|before_or_equal:today',
            'identificator' => 'required|string',
            'parasite_lab' => 'required|string',
            'parasite_state' => 'required|string',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,tif,tiff,pdf|max:51200',
            'photos' => 'nullable|array',
            'photos.*' => 'file|mimes:jpg,jpeg,png,webp,tif,tiff,pdf|max:51200',
            'storage_mode' => 'required|in:individual,pool',
            'pool_code' => 'nullable|string|max:255',
            'sub_project_id' => 'nullable|integer|exists:sub_projects,id',
        ];

        // Add validation rules based on selected model
        $model = request('model');
        switch ($model) {
            case 'Human samples':
                $rules['human_sample_id'] = 'required|array';
                $rules['human_sample_id.*'] = 'required|integer';
                break;
            case 'Animal samples':
                $rules['animal_sample_id'] = 'required|array';
                $rules['animal_sample_id.*'] = 'required|integer';
                break;
            case 'Environmental samples':
                $rules['environment_sample_id'] = 'required|array';
                $rules['environment_sample_id.*'] = 'required|integer';
                break;
        }

        $validator = Validator::make(request()->all(), $rules);

        // Add custom validation messages
        $validator->setCustomMessages([
            'model.required' => 'Please select a sample origin.',
            'parasite_species.required' => 'Please select a parasite species.',
            'date.required' => 'Please select an identification date.',
            'identificator.required' => 'Please select who identified the parasite.',
            'parasite_lab.required' => 'Please select a laboratory.',
            'parasite_state.required' => 'Please select the state of the samples.',
            'storage_mode.required' => 'Please select how the parasites will be stored.',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $errorMessages = [];
            foreach ($errors->all() as $error) {
                $errorMessages[] = $error;
            }
            session()->flash('error', 'Validation failed: '.implode(', ', $errorMessages));

            return back()->withErrors($validator)->withInput();
        }

        // Additional validation: if samples are selected, parasite codes must be provided
        $hasSelectedSamples = false;
        $selectedSampleIds = [];

        if ($model === 'Human samples' && request('human_sample_id')) {
            $selectedSampleIds = request('human_sample_id');
            $hasSelectedSamples = ! empty($selectedSampleIds);
        } elseif ($model === 'Animal samples' && request('animal_sample_id')) {
            $selectedSampleIds = request('animal_sample_id');
            $hasSelectedSamples = ! empty($selectedSampleIds);
        } elseif ($model === 'Environmental samples' && request('environment_sample_id')) {
            $selectedSampleIds = request('environment_sample_id');
            $hasSelectedSamples = ! empty($selectedSampleIds);
        }

        $storageMode = (string) request('storage_mode', 'individual');
        if ($hasSelectedSamples && $storageMode === 'individual' && empty(request('parasite_codes'))) {
            session()->flash('error', 'Please assign parasite codes for the selected samples.');

            return back()->withInput();
        }

        if ($storageMode === 'pool' && ! trim((string) request('pool_code'))) {
            session()->flash('error', 'Please select the resulting pool code.');

            return back()->withInput();
        }

        $projectId = (int) session('selected_project_id');

        try {
            if ($storageMode === 'individual') {
                // Check for duplicate parasite codes
                $parasiteCodes = request('parasite_codes');
                $allCodes = [];
                foreach ($parasiteCodes as $sampleId => $codes) {
                    foreach ($codes as $tickIndex => $code) {
                        if (! empty($code)) {
                            $allCodes[] = $code;
                        }
                    }
                }

                // Check for duplicates within the form
                if (count($allCodes) !== count(array_unique($allCodes))) {
                    session()->flash('error', 'Duplicate parasite codes detected. Please ensure each parasite has a unique code.');

                    return back()->withInput();
                }

                // Check for existing codes in database
                $existingCodes = ParasiteSamples::whereIn('code', $allCodes)->pluck('code')->toArray();
                if (! empty($existingCodes)) {
                    session()->flash('error', 'The following parasite codes already exist: '.implode(', ', $existingCodes));

                    return back()->withInput();
                }
            } else {
                $poolCode = trim((string) request('pool_code'));
                $poolExists = Pools::where('projects_id', session('selected_project_id'))
                    ->where('code', $poolCode)
                    ->exists();
                if ($poolExists) {
                    session()->flash('error', 'The selected pool code already exists: '.$poolCode);

                    return back()->withInput();
                }
            }

            $uploadedPhotoPaths = [];
            if (request()->hasFile('photos')) {
                foreach (request()->file('photos') as $uploadedPhoto) {
                    if ($uploadedPhoto) {
                        $uploadedPhotoPaths[] = $uploadedPhoto->store('parasite-photos', 'local');
                    }
                }
            } elseif (request()->hasFile('photo')) {
                $uploadedPhotoPaths[] = request()->file('photo')->store('parasite-photos', 'local');
            }

            $coverPhotoPath = $uploadedPhotoPaths[0] ?? null;

            $parasite_species_id = $this->service->check_or_create(
                ParasiteSpecies::class,
                ['name_scientific' => request('parasite_species')],
                [
                    'family' => request('new_species_family'),
                ]
            );

            $laboratories_id = $this->service->check_or_create(
                Laboratories::class,
                ['name' => request('parasite_lab')],
            );
            $identifierPeopleId = $this->resolveRegistrarPeopleId('identificator');
            $selectedSubProjectId = request('sub_project_id') ? (int) request('sub_project_id') : null;
            if (! SubProjectFlag::isSelectableByUser(Auth::user(), (int) $projectId, $selectedSubProjectId)) {
                session()->flash('error', 'Selected sub-project is not allowed for your user.');

                return back()->withInput();
            }

            // Determine the origin type and IDs based on the selected model
            $originType = null;
            $originIds = [];
            switch ($model) {
                case 'Human samples':
                    $originType = 'App\Models\HumanSamples';
                    $originIds = request('human_sample_id');
                    break;
                case 'Animal samples':
                    $originType = 'App\Models\AnimalSamples';
                    $originIds = request('animal_sample_id');
                    break;
                case 'Environmental samples':
                    $originType = 'App\Models\EnvironmentSamples';
                    $originIds = request('environment_sample_id');
                    break;
            }

            $project = Projects::findOrFail($projectId);
            $project_code = $project->code;

            $totalParasitesCreated = 0;
            $createdParasiteSampleIds = [];

            $autoCodes = [];
            $autoCodeIndex = 0;
            if ($storageMode === 'pool') {
                $tickCounts = (array) request('tick_counts', []);
                $needed = 0;
                foreach ($originIds as $originId) {
                    $needed += max(1, (int) ($tickCounts[$originId] ?? 1));
                }

                $existingPsCodes = ParasiteSamples::where('projects_id', $projectId)
                    ->where('code', 'like', $project_code.'-PS-%')
                    ->pluck('code');

                $usedNumbers = $existingPsCodes->map(function ($code) {
                    preg_match('/-PS-(\d+)$/', $code, $matches);

                    return isset($matches[1]) ? (int) $matches[1] : null;
                })->filter()->unique()->values();

                $usedSet = array_fill_keys($usedNumbers->all(), true);
                $serials = [];
                $n = 1;
                while (count($serials) < $needed) {
                    if (! isset($usedSet[$n])) {
                        $serials[] = $n;
                    }
                    $n++;
                }

                $autoCodes = array_map(fn (int $serial) => $project_code.'-PS-'.$serial, $serials);
            }

            // Create parasites for each selected origin
            foreach ($originIds as $originId) {
                $tickCount = (int) (request('tick_counts')[$originId] ?? 1);
                $parasiteCodes = $storageMode === 'individual' ? (request('parasite_codes')[$originId] ?? []) : [];

                // Create a parasite for each tick
                for ($tickIndex = 0; $tickIndex < $tickCount; $tickIndex++) {
                    $ps_code = $storageMode === 'pool'
                        ? ($autoCodes[$autoCodeIndex++] ?? null)
                        : ($parasiteCodes[$tickIndex] ?? null);

                    if (! $ps_code) {
                        continue; // Skip if no code for this specific tick
                    }

                    // Generate parasite code (PA-xxx)
                    $existingPaCodes = Parasites::where('projects_id', $projectId)
                        ->where('code', 'like', $project_code.'-PA-%')
                        ->pluck('code');

                    $usedNumbers = $existingPaCodes->map(function ($code) {
                        preg_match('/-PA-(\d+)$/', $code, $matches);

                        return isset($matches[1]) ? (int) $matches[1] : null;
                    })->filter()->sort()->values();

                    $newSerial = 1;
                    foreach ($usedNumbers as $num) {
                        if ($num != $newSerial) {
                            break;
                        }
                        $newSerial++;
                    }

                    $pa_code = $project_code.'-PA-'.$newSerial;

                    $new_parasite = Parasites::create([
                        'code' => $pa_code,
                        'parasite_species_id' => $parasite_species_id,
                        'stage' => request('stage'),
                        'sex' => request('sex'),
                        'state' => request('state'),
                        'date_identified' => request('date'),
                        'people_id' => $identifierPeopleId,
                        'laboratories_id' => $laboratories_id,
                        'photo_path' => $coverPhotoPath,
                        'projects_id' => $projectId,
                        'parasites_origin_type' => $originType,
                        'parasites_origin_id' => $originId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if ($uploadedPhotoPaths !== []) {
                        ParasiteObservationRecorder::createManyWithPhotos(
                            parasite: $new_parasite,
                            photoPaths: $uploadedPhotoPaths,
                            observedAt: request('date'),
                            notes: null,
                            peopleId: $identifierPeopleId,
                        );
                    }

                    $new_parasite_sample = ParasiteSamples::create([
                        'code' => $ps_code,
                        'parasites_id' => $new_parasite->id,
                        'parasite_sample_types_id' => ParasiteSampleTypes::where('name', 'Whole parasite')->first()->id,
                        'people_id' => $identifierPeopleId,
                        'laboratories_id' => $laboratories_id,
                        'projects_id' => $projectId,
                        'date_processed' => request('date'),
                        'photo_path' => $coverPhotoPath,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if ($uploadedPhotoPaths !== []) {
                        ParasiteSampleObservationRecorder::createManyWithPhotos(
                            sample: $new_parasite_sample,
                            photoPaths: $uploadedPhotoPaths,
                            observedAt: request('date'),
                            notes: null,
                            peopleId: $identifierPeopleId,
                        );
                    }

                    $createdParasiteSampleIds[] = $new_parasite_sample->id;

                    if ($storageMode === 'individual') {
                        // Individual: create tube for each sample as before
                        $existingTubeCodes = Tubes::where('code', 'like', $ps_code.'-%')
                            ->pluck('code');
                        $usedNumbers = $existingTubeCodes->map(function ($code) use ($ps_code) {
                            preg_match('/'.preg_quote($ps_code).'-(\d+)$/', $code, $matches);

                            return isset($matches[1]) ? (int) $matches[1] : null;
                        })->filter()->sort()->values();
                        $newSerial = 1;
                        foreach ($usedNumbers as $num) {
                            if ($num != $newSerial) {
                                break;
                            }
                            $newSerial++;
                        }
                        $tube_code = $ps_code.'-'.$newSerial;
                        $new_parasite_tube = Tubes::create([
                            'code' => $tube_code,
                            'tubes_content_type' => ParasiteSamples::class,
                            'tubes_content_id' => $new_parasite_sample->id,
                            'tube_type' => '1.5ml/2ml tube',
                            'purpose' => 'for parasite analysis',
                            'date_processed' => request('date'),
                            'projects_id' => $projectId,
                        ]);
                        SubProjectFlag::assign($new_parasite_sample, $selectedSubProjectId);
                    }
                    $totalParasitesCreated++;
                }
            }
            // If pooled, create pool, pool_contents, and tube for the pool
            if ($storageMode === 'pool' && count($createdParasiteSampleIds) > 0) {
                $pool_code = trim((string) request('pool_code'));
                $pool = Pools::create([
                    'code' => $pool_code,
                    'nr_pooled' => count($createdParasiteSampleIds),
                    'date_pooled' => request('date'),
                    'people_id' => $identifierPeopleId,
                    'laboratories_id' => $laboratories_id,
                    'projects_id' => $projectId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                // Link each ParasiteSample to the pool
                foreach ($createdParasiteSampleIds as $ps_id) {
                    $pool->pool_contents()->create([
                        'samples_type' => ParasiteSamples::class,
                        'samples_id' => $ps_id,
                        'pools_id' => $pool->id,
                    ]);
                }
                // Create tube for the pool with serial
                // Use the pool code as prefix (not any PA or PS codes)
                // Find existing tubes for this pool
                $existingPoolTubeCodes = Tubes::where('code', 'like', $pool_code.'-%')->pluck('code');
                $usedTubeSerials = $existingPoolTubeCodes->map(function ($code) use ($pool_code) {
                    preg_match('/'.preg_quote($pool_code).'-(\d+)$/', $code, $matches);

                    return isset($matches[1]) ? (int) $matches[1] : null;
                })->filter()->sort()->values();
                $tubeSerial = 1;
                foreach ($usedTubeSerials as $num) {
                    if ($num != $tubeSerial) {
                        break;
                    }
                    $tubeSerial++;
                }
                $pool_tube_code = $pool_code.'-'.$tubeSerial;
                $pool_tube = Tubes::create([
                    'code' => $pool_tube_code,
                    'tubes_content_type' => Pools::class,
                    'tubes_content_id' => $pool->id,
                    'tube_type' => '1.5ml/2ml tube',
                    'purpose' => 'for pooling',
                    'date_processed' => request('date'),
                    'projects_id' => $projectId,
                ]);
            }

            $success = $totalParasitesCreated.' parasite(s) identified successfully!';
            if ($storageMode === 'pool' && trim((string) request('pool_code')) !== '') {
                $success .= ' Stored as pool '.trim((string) request('pool_code')).'.';
            }

            session()->flash('success', $success);

            $user = Auth::user();

            NotificationController::create(
                'parasite_sample_created',
                'New Parasites Identified',
                $user->people->first_name.' identified '.$totalParasitesCreated.' parasite'.($totalParasitesCreated > 1 ? 's.' : '.'),
                '/samples/parasites/list',
                $projectId
            );

            return back();
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());

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
}
