<?php

namespace App\Http\Controllers;

use App\Models\Animals;
use App\Models\AnimalSamples;
use App\Models\AnimalSpecies;
use App\Models\Humans;
use App\Models\Locations;
use App\Models\Organizations;
use App\Models\Projects;
use App\Models\SampleTypes;
use App\Models\SamplingSites;
use App\Services\AnimalSamplesService;
use App\Support\ProjectPermission;
use App\Support\SubProjectFlag;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AnimalSamplesController extends Controller
{
    protected $service;

    public function __construct(AnimalSamplesService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        $data = $this->service->dataForCreate();
        $mode = (string) request()->query('mode', '');
        if (! in_array($mode, ['form', 'import', 'table'], true)) {
            $mode = (string) old('register_mode', 'form');
        }
        if (! in_array($mode, ['form', 'import', 'table'], true)) {
            $mode = 'form';
        }
        $projectId = (int) session('selected_project_id');
        $user = Auth::user();
        $tableExistingAnimals = Animals::query()
            ->with('animal_species:id,name_common')
            ->where('projects_id', $projectId)
            ->get([
                'id',
                'field_label',
                'animal_species_id',
                'sex',
                'age',
                'owner_type',
                'owner_id',
            ])
            ->map(function (Animals $animal): array {
                return [
                    'id' => (int) $animal->id,
                    'field_label' => (string) ($animal->field_label ?? ''),
                    'animal_species' => (string) data_get($animal, 'animal_species.name_common', ''),
                    'sex' => (string) ($animal->sex ?? ''),
                    'age' => (string) ($animal->age ?? ''),
                    'owner_type' => (string) ($animal->owner_type ?? ''),
                    'owner_id' => (int) ($animal->owner_id ?? 0),
                ];
            })
            ->values();

        return view('samples.animals.create', array_merge($data, [
            'selected_animals' => $this->selectedAnimalsFromOldInput(),
            'can_assign_registrar' => $user ? ProjectPermission::canAssignRegistrar($user, $projectId) : false,
            'locked_registrar_people_id' => $user ? ProjectPermission::currentRegistrarPeopleId($user) : null,
            'sub_project_options' => SubProjectFlag::optionsForUser($user, $projectId),
            'table_existing_animals' => $tableExistingAnimals,
            'register_mode' => $mode,
        ]));
    }

    public function store()
    {
        if ((string) request('register_mode') === 'table') {
            return $this->storeFromTable();
        }

        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);
        $project_code = $project->code;
        $user = Auth::user();

        $existingSpecies = AnimalSpecies::pluck('name_common')->toArray();

        $rules = [
            'animal_id' => 'required_if:animal_existing,Yes',
            'sample_type' => 'required',
            'date' => 'required|date|before_or_equal:today',
            'sampling_site' => 'required|string',
            'area' => 'nullable|string|max:50|min:2',
            'latitude' => 'nullable|numeric|decimal:2,8',
            'longitude' => 'nullable|numeric|decimal:2,8',
            'location' => 'required|string',
            'new_sample_types' => 'array',
            'new_sample_types.*.name' => 'required|string|max:255',
            'new_sample_types.*.category' => 'required|in:host_derived,non_host_derived',
            'sub_project_id' => 'nullable|integer|exists:sub_projects,id',
        ];

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {
            /** @var array<int|string, array{name?:string, category?:string}> $newSampleTypes */
            $newSampleTypes = (array) request('new_sample_types', []);
            $newSampleTypeCategoriesByName = collect($newSampleTypes)
                ->mapWithKeys(function (array $row): array {
                    $name = trim((string) ($row['name'] ?? ''));
                    $category = (string) ($row['category'] ?? '');

                    return $name !== '' ? [$name => $category] : [];
                })
                ->filter(fn (string $category) => in_array($category, ['host_derived', 'non_host_derived'], true))
                ->all();

            $sampling_site_id = $this->service->check_or_create(
                SamplingSites::class,
                ['name' => request('sampling_site')]
            );

            $locations_id = $this->service->check_or_create(
                Locations::class,
                ['name' => request('location')],
            );

            $animals_id = request('animal_id');
            $collectorPeopleId = $this->resolveRegistrarPeopleId('collector');
            $selectedSubProjectId = request('sub_project_id') ? (int) request('sub_project_id') : null;
            if (! SubProjectFlag::isSelectableByUser($user, (int) $projectId, $selectedSubProjectId)) {
                session()->flash('error', 'Selected sub-project is not allowed for your user.');

                return back()->withInput();
            }

            foreach ($animals_id as $animal_id) {

                foreach (request('sample_type') as $sample_type) {

                    $existingAsCodes = AnimalSamples::where('projects_id', $projectId)
                        ->where('code', 'like', $project_code.'-AS-%')
                        ->pluck('code');

                    $usedNumbers = $existingAsCodes->map(function ($code) {
                        preg_match('/-AS-(\d+)$/', $code, $matches);

                        return isset($matches[1]) ? (int) $matches[1] : null;
                    })->filter()->sort()->values();

                    $newSerial = 1;
                    foreach ($usedNumbers as $num) {
                        if ($num != $newSerial) {
                            break;
                        }
                        $newSerial++;
                    }

                    $as_code = $project_code.'-AS-'.$newSerial;

                    $sample_types_id = $this->service->check_or_create(
                        SampleTypes::class,
                        ['name' => $sample_type],
                        isset($newSampleTypeCategoriesByName[$sample_type])
                            ? ['category' => $newSampleTypeCategoriesByName[$sample_type]]
                            : []
                    );

                    $animalSample = AnimalSamples::create([
                        'code' => $as_code,
                        'animals_id' => $animal_id,
                        'sample_types_id' => $sample_types_id,
                        'date_collected' => request('date'),
                        'people_id' => $collectorPeopleId,
                        'sampling_sites_id' => $sampling_site_id,
                        'area' => request('area'),
                        'latitude' => request('latitude'),
                        'longitude' => request('longitude'),
                        'immobilization_reason' => request('reason_immobilization'),
                        'locations_id' => $locations_id,
                        'projects_id' => $projectId,
                        'storage_state' => request('preservant'),
                        'date_received' => request('date_received'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    SubProjectFlag::assign($animalSample, $selectedSubProjectId);
                }

            }

            $count = count(request('sample_type')) * count(request('animal_id'));

            // Flash success message to the session
            session()->flash('success', 'Animal sample registered successfully!');

            // Get the authenticated user
            // Create notification
            NotificationController::create(
                'animal_sample_created',
                'New Animal Samples',
                $user->people->first_name.' registered '.$count.' animal sample'.($count > 1 ? 's.' : '.'),
                '/samples/animals/list',
                $projectId  // Use dynamic project ID
            );

            return back();
        } catch (\Exception $e) {
            session()->flash('error', 'An unexpected error occurred: '.$e->getMessage());

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

        $validator = Validator::make(
            ['table_rows' => $rows, 'sub_project_id' => request('sub_project_id')],
            [
                'table_rows' => 'required|array|min:1',
                'table_rows.*.animal_species' => 'required|string|exists:animal_species,name_common',
                'table_rows.*.field_label' => 'required|string|max:255',
                'table_rows.*.sex' => 'required|in:Male,Female,NA',
                'table_rows.*.age' => 'required|in:Juvenile,Sub-adult,Adult,Old,NA',
                'table_rows.*.owner_type' => 'required|in:individual,organization',
                'table_rows.*.owner_person' => 'required_if:table_rows.*.owner_type,individual|nullable|exists:humans,id',
                'table_rows.*.owner_organization' => 'required_if:table_rows.*.owner_type,organization|nullable|exists:organizations,id',
                'table_rows.*.sample_type' => 'required|string|max:255',
                'table_rows.*.sample_type_category' => 'nullable|in:host_derived,non_host_derived',
                'table_rows.*.date' => 'required|date|before_or_equal:today',
                'table_rows.*.sampling_site' => 'required|string|max:255',
                'table_rows.*.location' => 'required|string|max:255',
                'table_rows.*.area' => 'nullable|string|max:50|min:2',
                'table_rows.*.latitude' => 'nullable|numeric|decimal:2,8',
                'table_rows.*.longitude' => 'nullable|numeric|decimal:2,8',
                'table_rows.*.preservant' => 'nullable|string|max:255',
                'table_rows.*.date_received' => 'nullable|date',
                'table_rows.*.reason_immobilization' => 'nullable|string|max:255',
                'table_rows.*.collector' => 'nullable|integer|exists:people,id',
                'sub_project_id' => 'nullable|integer|exists:sub_projects,id',
            ]
        );

        $existingSampleTypes = SampleTypes::query()->pluck('name')->map(fn ($name) => mb_strtolower(trim((string) $name)))->all();
        $existingSampleTypeLookup = array_fill_keys($existingSampleTypes, true);

        $validator->after(function ($validator) use ($rows, $existingSampleTypeLookup): void {
            foreach ($rows as $index => $row) {
                $ownerType = (string) Arr::get($row, 'owner_type');
                if ($ownerType === 'individual' && ! Arr::get($row, 'owner_person')) {
                    $validator->errors()->add("table_rows.$index.owner_person", 'Owner is required when owner type is individual.');
                }

                if ($ownerType === 'organization' && ! Arr::get($row, 'owner_organization')) {
                    $validator->errors()->add("table_rows.$index.owner_organization", 'Owner organization is required when owner type is organization.');
                }

                $sampleType = mb_strtolower(trim((string) Arr::get($row, 'sample_type', '')));
                $isNewSampleType = $sampleType !== '' && ! isset($existingSampleTypeLookup[$sampleType]);
                if ($isNewSampleType && ! in_array((string) Arr::get($row, 'sample_type_category', ''), ['host_derived', 'non_host_derived'], true)) {
                    $validator->errors()->add("table_rows.$index.sample_type_category", 'Select host/non-host category for new sample type.');
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

        $animalSerial = $this->nextSerialForProjectPattern(Animals::class, $projectId, $projectCode.'-AN-');
        $sampleSerial = $this->nextSerialForProjectPattern(AnimalSamples::class, $projectId, $projectCode.'-AS-');
        $createdSamples = 0;

        DB::transaction(function () use (
            $rows,
            $projectId,
            $projectCode,
            $selectedSubProjectId,
            &$animalSerial,
            &$sampleSerial,
            &$createdSamples
        ): void {
            foreach ($rows as $row) {
                $fieldLabel = trim((string) $row['field_label']);
                $animal = Animals::query()
                    ->where('projects_id', $projectId)
                    ->whereRaw('LOWER(field_label) = ?', [mb_strtolower($fieldLabel)])
                    ->first();

                if (! $animal) {
                    $speciesId = (int) AnimalSpecies::query()
                        ->where('name_common', (string) $row['animal_species'])
                        ->value('id');

                    $ownerType = (string) $row['owner_type'] === 'individual' ? Humans::class : Organizations::class;
                    $ownerId = (string) $row['owner_type'] === 'individual'
                        ? (int) $row['owner_person']
                        : (int) $row['owner_organization'];

                    $animal = Animals::create([
                        'code' => $projectCode.'-AN-'.$animalSerial++,
                        'animal_species_id' => $speciesId,
                        'field_label' => $fieldLabel,
                        'sex' => (string) $row['sex'],
                        'age' => (string) $row['age'],
                        'owner_type' => $ownerType,
                        'owner_id' => $ownerId,
                        'projects_id' => $projectId,
                    ]);
                }

                $sampleTypeId = $this->service->check_or_create(
                    SampleTypes::class,
                    ['name' => (string) $row['sample_type']],
                    in_array((string) Arr::get($row, 'sample_type_category'), ['host_derived', 'non_host_derived'], true)
                        ? ['category' => (string) Arr::get($row, 'sample_type_category')]
                        : []
                );
                $samplingSiteId = $this->service->check_or_create(
                    SamplingSites::class,
                    ['name' => (string) $row['sampling_site']]
                );
                $locationId = $this->service->check_or_create(
                    Locations::class,
                    ['name' => (string) $row['location']]
                );

                $animalSample = AnimalSamples::create([
                    'code' => $projectCode.'-AS-'.$sampleSerial++,
                    'animals_id' => $animal->id,
                    'sample_types_id' => $sampleTypeId,
                    'date_collected' => (string) $row['date'],
                    'people_id' => $this->resolveRegistrarPeopleIdFromValue(Arr::get($row, 'collector')),
                    'sampling_sites_id' => $samplingSiteId,
                    'area' => Arr::get($row, 'area'),
                    'latitude' => Arr::get($row, 'latitude'),
                    'longitude' => Arr::get($row, 'longitude'),
                    'immobilization_reason' => Arr::get($row, 'reason_immobilization'),
                    'locations_id' => $locationId,
                    'projects_id' => $projectId,
                    'storage_state' => Arr::get($row, 'preservant'),
                    'date_received' => Arr::get($row, 'date_received'),
                ]);

                SubProjectFlag::assign($animalSample, $selectedSubProjectId);
                $createdSamples++;
            }
        });

        session()->flash('success', $createdSamples.' animal samples registered successfully via table.');

        NotificationController::create(
            'animal_sample_created',
            'New Animal Samples',
            $user->people->first_name.' registered '.$createdSamples.' animal samples via table.',
            '/samples/animals/list',
            $projectId
        );

        return back();
    }

    private function selectedAnimalsFromOldInput(): Collection
    {
        $selectedIds = collect(old('animal_id', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($selectedIds->isEmpty()) {
            return collect();
        }

        return Animals::query()
            ->whereIn('id', $selectedIds)
            ->orderBy('code')
            ->get(['id', 'code']);
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

    private function resolveRegistrarPeopleIdFromValue($value): ?int
    {
        $user = Auth::user();
        if (! $user || ! $user->people) {
            return $value ? (int) $value : null;
        }

        $projectId = (int) session('selected_project_id');
        if (! ProjectPermission::canAssignRegistrar($user, $projectId)) {
            return (int) $user->people->id;
        }

        return $value ? (int) $value : (int) $user->people->id;
    }

    private function nextSerialForProjectPattern(string $modelClass, int $projectId, string $prefix): int
    {
        $codes = $modelClass::query()
            ->where('projects_id', $projectId)
            ->where('code', 'like', $prefix.'%')
            ->pluck('code');

        $usedNumbers = $codes->map(function ($code) use ($prefix) {
            $escapedPrefix = preg_quote($prefix, '/');
            preg_match('/^'.$escapedPrefix.'(\d+)$/', (string) $code, $matches);

            return isset($matches[1]) ? (int) $matches[1] : null;
        })->filter()->sort()->values();

        $serial = 1;
        foreach ($usedNumbers as $usedNumber) {
            if ($usedNumber !== $serial) {
                break;
            }
            $serial++;
        }

        return $serial;
    }

    private function normalizeTableRows(array $rows): array
    {
        return collect($rows)
            ->map(function ($row): array {
                return collect((array) $row)
                    ->map(fn ($value) => is_string($value) ? trim($value) : $value)
                    ->all();
            })
            ->filter(function (array $row): bool {
                return collect($row)
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->isNotEmpty();
            })
            ->values()
            ->all();
    }
}
