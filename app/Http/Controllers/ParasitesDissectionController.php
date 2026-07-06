<?php

namespace App\Http\Controllers;

use App\Enums\ParasiteStatus;
use App\Models\Laboratories;
use App\Models\Parasites;
use App\Models\ParasiteSamples;
use App\Models\ParasiteSampleTypes;
use App\Models\Pools;
use App\Models\Projects;
use App\Models\Tubes;
use App\Services\ParasiteSamplesService;
use App\Support\LookupTableData;
use App\Support\ProjectPermission;
use App\Support\SubProjectFlag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ParasitesDissectionController extends Controller
{
    public function __construct(protected ParasiteSamplesService $service) {}

    public function create(): View
    {
        $projectId = (int) session('selected_project_id');
        $people = Projects::find($projectId)?->people ?? collect();
        $user = Auth::user();

        $selectedParasites = $this->selectedParasitesFromOldInput();

        $currentPeopleId = $user?->people?->id;
        $sortedPeople = $people->sortBy([
            fn ($a, $b) => ($currentPeopleId !== null && (int) $a->id === $currentPeopleId ? 0 : 1)
                <=> ($currentPeopleId !== null && (int) $b->id === $currentPeopleId ? 0 : 1),
            fn ($a, $b) => strcasecmp((string) $a->last_name, (string) $b->last_name),
            fn ($a, $b) => strcasecmp((string) $a->first_name, (string) $b->first_name),
        ])->values();

        return view('samples.parasites.dissection.create', [
            'parasite_sample_types' => ParasiteSampleTypes::query()->orderBy('name')->get(),
            'laboratories_by_country' => $this->service->laboratories_by_country(),
            'laboratory_lookup_rows' => LookupTableData::laboratories(),
            'people' => $sortedPeople,
            'selected_parasites' => $selectedParasites,
            'can_assign_registrar' => $user ? ProjectPermission::canAssignRegistrar($user, $projectId) : false,
            'locked_registrar_people_id' => $user ? ProjectPermission::currentRegistrarPeopleId($user) : null,
            'sub_project_options' => SubProjectFlag::optionsForUser($user, $projectId),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $projectId = (int) session('selected_project_id');
        $project = Projects::findOrFail($projectId);
        $projectCode = (string) $project->code;

        $rules = [
            'parasites_id' => 'required|array|min:1',
            'parasites_id.*' => 'required|integer',
            'parasite_sample_types' => 'required|array|min:1',
            'parasite_sample_types.*' => 'required|string|max:255',
            'storage_mode' => 'required|in:individual,pool',
            'date_processed' => 'required|date|before_or_equal:today',
            'people_id' => 'required|integer',
            'laboratory' => 'required|string|max:255',
            'tube_alias_codes' => 'array',
            'tube_alias_codes.*' => 'nullable|string|max:255',
            'pool_tube_alias' => 'nullable|string|max:255',
            'sub_project_id' => 'nullable|integer|exists:sub_projects,id',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        $selectedSubProjectId = $request->input('sub_project_id') ? (int) $request->input('sub_project_id') : null;
        if (! SubProjectFlag::isSelectableByUser(Auth::user(), $projectId, $selectedSubProjectId)) {
            session()->flash('error', 'Selected sub-project is not allowed for your user.');

            return back()->withInput();
        }

        $parasiteIds = array_values(array_unique(array_map('intval', (array) $request->input('parasites_id', []))));
        $sampleTypeNames = collect((array) $request->input('parasite_sample_types', []))
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values();

        if (empty($parasiteIds) || $sampleTypeNames->isEmpty()) {
            session()->flash('error', 'Please select parasites and dissected parasite sample types.');

            return back()->withInput();
        }

        try {
            $dissectorPeopleId = $this->resolveRegistrarPeopleId('people_id');
            $laboratoriesId = $this->service->check_or_create(
                Laboratories::class,
                ['name' => $request->input('laboratory')]
            );

            $needed = count($parasiteIds) * $sampleTypeNames->count();
            $serials = $this->nextAvailableSerialsForPrefix($projectId, $projectCode, $needed);
            $serialIndex = 0;

            $createdParasiteSampleIds = [];
            $createdCount = 0;
            $createdTubesCount = 0;
            $createdPoolsCount = 0;
            $createdPoolCode = null;

            foreach ($parasiteIds as $parasiteId) {
                foreach ($sampleTypeNames as $typeName) {
                    $typeId = $this->service->check_or_create(
                        ParasiteSampleTypes::class,
                        ['name' => $typeName]
                    );

                    $serial = $serials[$serialIndex++] ?? null;
                    if (! $serial) {
                        continue;
                    }

                    $psCode = $projectCode.'-PS-'.$serial;

                    $parasiteSample = ParasiteSamples::create([
                        'code' => $psCode,
                        'parasites_id' => $parasiteId,
                        'parasite_sample_types_id' => $typeId,
                        'people_id' => $dissectorPeopleId,
                        'laboratories_id' => $laboratoriesId,
                        'projects_id' => $projectId,
                        'date_processed' => $request->input('date_processed'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $createdParasiteSampleIds[] = $parasiteSample->id;
                    $createdCount++;

                    SubProjectFlag::assign($parasiteSample, $selectedSubProjectId);

                    if ($request->input('storage_mode') === 'individual') {
                        $aliasKey = $parasiteId.'|'.$typeName;
                        $aliasCode = filled($request->input('tube_alias_codes')[$aliasKey] ?? null)
                            ? trim((string) $request->input('tube_alias_codes')[$aliasKey])
                            : null;

                        $tube = Tubes::create([
                            'code' => $psCode.'-1',
                            'alias_code' => $aliasCode,
                            'tubes_content_type' => ParasiteSamples::class,
                            'tubes_content_id' => $parasiteSample->id,
                            'tube_type' => '1.5ml/2ml tube',
                            'purpose' => 'tick dissection',
                            'date_processed' => $request->input('date_processed'),
                            'projects_id' => $projectId,
                        ]);
                        SubProjectFlag::assign($tube, $selectedSubProjectId);
                        $createdTubesCount++;
                    }
                }
            }

            if ($request->input('storage_mode') === 'pool' && count($createdParasiteSampleIds) > 0) {
                $poolCode = $this->nextAvailablePoolCode($projectId, $projectCode);
                $createdPoolCode = $poolCode;

                $pool = Pools::create([
                    'code' => $poolCode,
                    'nr_pooled' => count($createdParasiteSampleIds),
                    'date_pooled' => $request->input('date_processed'),
                    'people_id' => $dissectorPeopleId,
                    'laboratories_id' => $laboratoriesId,
                    'projects_id' => $projectId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                SubProjectFlag::assign($pool, $selectedSubProjectId);

                foreach ($createdParasiteSampleIds as $psId) {
                    $pool->pool_contents()->create([
                        'samples_type' => ParasiteSamples::class,
                        'samples_id' => $psId,
                        'pools_id' => $pool->id,
                    ]);
                }

                $poolTubeCode = $this->nextAvailableTubeCodeForPrefix($poolCode);

                $poolAliasCode = filled($request->input('pool_tube_alias'))
                    ? trim((string) $request->input('pool_tube_alias'))
                    : null;

                $poolTube = Tubes::create([
                    'code' => $poolTubeCode,
                    'alias_code' => $poolAliasCode,
                    'tubes_content_type' => Pools::class,
                    'tubes_content_id' => $pool->id,
                    'tube_type' => '1.5ml/2ml tube',
                    'purpose' => 'tick dissection pool',
                    'date_processed' => $request->input('date_processed'),
                    'projects_id' => $projectId,
                ]);
                SubProjectFlag::assign($poolTube, $selectedSubProjectId);
                $createdTubesCount++;
                $createdPoolsCount++;
            }

            Parasites::query()
                ->where('projects_id', $projectId)
                ->whereIn('id', $parasiteIds)
                ->update(['status' => ParasiteStatus::Dissected->value]);

            $x = count($parasiteIds);
            $y = $createdCount;
            $z = $createdTubesCount;
            $k = $createdPoolsCount;

            $summary = $x.' parasite'.($x !== 1 ? 's' : '').' dissected resulting in '.$y.' parasite sample'.($y !== 1 ? 's' : '').', '.$z.' tube'.($z !== 1 ? 's' : '').' and '.$k.' pool'.($k !== 1 ? 's' : '').'.';
            if ($createdPoolCode) {
                $summary .= ' Pool code: '.$createdPoolCode.'.';
            }

            session()->flash('success', 'Tick dissection registered successfully! '.$summary);

            $user = Auth::user();
            if ($user) {
                NotificationController::create(
                    'parasite_dissection_created',
                    'New Parasites Dissected',
                    $user->people->first_name.' registered a parasite dissection: '.$summary,
                    '/samples/parasites/list',
                    $projectId
                );
            }

            return back();
        } catch (\Exception $e) {
            session()->flash('error', 'An unexpected error occurred: '.$e->getMessage());

            return back()->withInput();
        }
    }

    private function selectedParasitesFromOldInput(): Collection
    {
        $selectedIds = collect(old('parasites_id', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($selectedIds->isEmpty()) {
            return collect();
        }

        $aliasSubquery = Parasites::storageTubeAliasSubquery();

        return Parasites::query()
            ->whereIn('id', $selectedIds)
            ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
            ->select([
                'parasites.id',
                'parasites.code',
                'parasite_species.name_scientific as species_name',
                DB::raw($aliasSubquery.' as parasite_alias_code'),
            ])
            ->orderBy('code')
            ->get();
    }

    /**
     * @return array<int, int>
     */
    private function nextAvailableSerialsForPrefix(int $projectId, string $projectCode, int $needed): array
    {
        $existing = ParasiteSamples::query()
            ->where('projects_id', $projectId)
            ->where('code', 'like', $projectCode.'-PS-%')
            ->pluck('code');

        $used = $existing->map(function (string $code): ?int {
            if (preg_match('/-PS-(\d+)$/', $code, $m)) {
                return (int) $m[1];
            }

            return null;
        })->filter()->unique()->values();

        $usedSet = array_fill_keys($used->all(), true);

        $serials = [];
        $n = 1;
        while (count($serials) < $needed) {
            if (! isset($usedSet[$n])) {
                $serials[] = $n;
            }
            $n++;
        }

        return $serials;
    }

    private function nextAvailablePoolCode(int $projectId, string $projectCode): string
    {
        $existing = Pools::query()
            ->where('projects_id', $projectId)
            ->where('code', 'like', $projectCode.'-PO-%')
            ->pluck('code');

        $used = $existing->map(function (string $code): ?int {
            if (preg_match('/-PO-(\d+)$/', $code, $m)) {
                return (int) $m[1];
            }

            return null;
        })->filter()->unique()->values();

        $usedSet = array_fill_keys($used->all(), true);

        $n = 1;
        while (isset($usedSet[$n])) {
            $n++;
        }

        return $projectCode.'-PO-'.$n;
    }

    private function nextAvailableTubeCodeForPrefix(string $prefix): string
    {
        $existing = Tubes::query()->where('code', 'like', $prefix.'-%')->pluck('code');

        $used = $existing->map(function (string $code) use ($prefix): ?int {
            if (preg_match('/'.preg_quote($prefix, '/').'-(\d+)$/', $code, $m)) {
                return (int) $m[1];
            }

            return null;
        })->filter()->unique()->values();

        $usedSet = array_fill_keys($used->all(), true);
        $n = 1;
        while (isset($usedSet[$n])) {
            $n++;
        }

        return $prefix.'-'.$n;
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
