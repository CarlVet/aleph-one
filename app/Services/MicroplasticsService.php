<?php

namespace App\Services;

use App\Models\AnimalSamples;
use App\Models\Countries;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\Microplastics;
use App\Models\MpsTypes;
use App\Models\Organizations;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\Projects;
use App\Models\Protocols;
use App\Models\Techniques;
use App\Models\Tubes;
use App\Support\SubProjectFlag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MicroplasticsService
{
    protected ?int $projectId;

    public function __construct()
    {
        $this->projectId = session('selected_project_id');
    }

    public function getProjectId(): ?int
    {
        return $this->projectId;
    }

    public function check_or_create($model, $conditions, $attributes = [])
    {
        $existingValue = $model::where($conditions)->first();

        if (! $existingValue) {
            $model::create(array_merge($conditions, $attributes));

            return $model::where($conditions)->first()->id;
        }

        return $existingValue->id;
    }

    public function laboratoriesByCountry(): array
    {
        $laboratories = Laboratories::with('countries')->get();
        $labsByCountry = [];

        foreach ($laboratories as $lab) {
            $country = $lab->countries->name ?? 'Unknown country';
            $name = $lab->name ?? '';

            if ($name !== '') {
                $labsByCountry[$country][] = [
                    'name' => $name,
                    'type' => 'laboratory',
                ];
            }
        }

        ksort($labsByCountry);

        foreach ($labsByCountry as $country => $labsList) {
            usort($labsByCountry[$country], fn (array $a, array $b): int => strcmp($a['name'], $b['name']));
        }

        return $labsByCountry;
    }

    public function dataForCreate(): array
    {
        $project = Projects::query()->find($this->projectId);

        return [
            'selected_human_tubes' => $this->selectedTubesFromOldInput('human_tube_id'),
            'selected_animal_tubes' => $this->selectedTubesFromOldInput('animal_tube_id'),
            'selected_environment_tubes' => $this->selectedTubesFromOldInput('environment_tube_id'),
            'selected_parasite_tubes' => $this->selectedTubesFromOldInput('parasite_tube_id'),
            'selected_pool_tubes' => $this->selectedTubesFromOldInput('pool_tube_id'),
            'laboratories_by_country' => $this->laboratoriesByCountry(),
            'people' => $project?->people ?? collect(),
            'microplastic_protocols' => Protocols::query()
                ->with('techniques')
                ->whereHas('techniques', fn ($query) => $query->where('type', 'Microplastics identification'))
                ->orderBy('name')
                ->get(),
            'microplastic_techniques' => Techniques::query()
                ->where('type', 'Microplastics identification')
                ->orderBy('name')
                ->get(),
            'organizations' => Organizations::query()->get(['id', 'name']),
            'countries' => Countries::query()->get(['id', 'name']),
            'table_tube_options' => $this->tableTubeOptions(),
            'mps_types' => MpsTypes::query()->orderBy('name')->get(),
        ];
    }

    public function sourceTypeForModelLabel(string $label): ?string
    {
        return match ($label) {
            'Human samples' => HumanSamples::class,
            'Animal samples' => AnimalSamples::class,
            'Environmental samples', 'Environment samples' => EnvironmentSamples::class,
            'Parasite samples' => ParasiteSamples::class,
            'Pools' => Pools::class,
            default => null,
        };
    }

    public function requestKeyForModelLabel(string $label): ?string
    {
        return match ($label) {
            'Human samples' => 'human_tube_id',
            'Animal samples' => 'animal_tube_id',
            'Environmental samples', 'Environment samples' => 'environment_tube_id',
            'Parasite samples' => 'parasite_tube_id',
            'Pools' => 'pool_tube_id',
            default => null,
        };
    }

    /**
     * @param  array<int, int|string>  $tubeIds
     * @param  array{sample_weight: float|int|string|null, r_coeff: float|int|string|null, mps_type: array<int, string>|string|null, m_feret: float|int|string|null, identification_date: string|null, source_measurement_mode?: string|null}  $attributes
     */
    public function registerFromTubes(
        int $projectId,
        string $sourceType,
        array $tubeIds,
        string $protocolName,
        string $laboratoryName,
        int $peopleId,
        array $attributes,
        ?int $subProjectId = null
    ): int {
        $project = Projects::query()->findOrFail($projectId);
        $protocolId = (int) Protocols::query()->where('name', $protocolName)->value('id');
        if ($protocolId <= 0) {
            throw new \RuntimeException('Selected microplastics protocol was not found. Please create it first.');
        }
        $laboratoryId = $this->check_or_create(Laboratories::class, ['name' => $laboratoryName]);
        $mpsTypeIds = $this->resolveMpsTypeIds($attributes['mps_type'] ?? null);
        $sourceMeasurementMode = $this->normalizeSourceMeasurementMode($attributes['source_measurement_mode'] ?? null);

        return DB::transaction(function () use ($project, $projectId, $sourceType, $tubeIds, $protocolId, $laboratoryId, $peopleId, $attributes, $subProjectId, $mpsTypeIds, $sourceMeasurementMode): int {
            $created = 0;
            $sourceGroups = [];

            foreach ($tubeIds as $tubeId) {
                $tube = Tubes::query()
                    ->where('projects_id', $projectId)
                    ->where('tubes_content_type', $sourceType)
                    ->findOrFail((int) $tubeId);

                $sourceKey = $tube->tubes_content_type.'#'.$tube->tubes_content_id;
                $sourceGroups[$sourceKey][] = $tube;
            }

            foreach ($sourceGroups as $tubesForSource) {
                if (! is_array($tubesForSource) || $tubesForSource === []) {
                    continue;
                }

                $measurementUnits = $sourceMeasurementMode === 'pooled'
                    ? [$tubesForSource[0]]
                    : $tubesForSource;

                foreach ($measurementUnits as $tube) {
                    foreach ($mpsTypeIds as $mpsTypeId) {
                        $microplasticCode = $this->nextProjectCode($project->code, 'MP');
                        $microplastic = Microplastics::query()->create([
                            'code' => $microplasticCode,
                            'microplastics_content_type' => $tube->tubes_content_type,
                            'microplastics_content_id' => $tube->tubes_content_id,
                            'sample_weight' => $attributes['sample_weight'],
                            'r_coeff' => $attributes['r_coeff'],
                            'mps_types_id' => $mpsTypeId,
                            'm_feret' => $attributes['m_feret'],
                            'identification_date' => $attributes['identification_date'],
                            'protocols_id' => $protocolId,
                            'laboratories_id' => $laboratoryId,
                            'people_id' => $peopleId,
                            'projects_id' => $projectId,
                        ]);

                        SubProjectFlag::assign($microplastic, $subProjectId);

                        $created++;
                    }
                }
            }

            return $created;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function registerFromTableRows(int $projectId, array $rows, ?int $subProjectId = null): int
    {
        $project = Projects::query()->findOrFail($projectId);

        return DB::transaction(function () use ($project, $projectId, $rows, $subProjectId): int {
            $created = 0;

            foreach ($rows as $row) {
                $tube = Tubes::query()
                    ->where('projects_id', $projectId)
                    ->findOrFail((int) $row['tube_id']);

                if (! in_array($tube->tubes_content_type, $this->eligibleContentTypes(), true)) {
                    throw new \RuntimeException('Invalid tube source selected for microplastics registration.');
                }

                $protocolId = (int) Protocols::query()->where('name', (string) $row['protocol_name'])->value('id');
                if ($protocolId <= 0) {
                    throw new \RuntimeException('Selected microplastics protocol was not found. Please create it first.');
                }
                $laboratoryId = $this->check_or_create(Laboratories::class, ['name' => (string) $row['laboratory']]);
                $mpsTypeId = $this->resolveOrCreateMpsTypeId($row['mps_type'] ?? null);

                $microplasticCode = $this->nextProjectCode($project->code, 'MP');
                $microplastic = Microplastics::query()->create([
                    'code' => $microplasticCode,
                    'microplastics_content_type' => $tube->tubes_content_type,
                    'microplastics_content_id' => $tube->tubes_content_id,
                    'sample_weight' => $row['sample_weight'] ?: null,
                    'r_coeff' => $row['r_coeff'] ?: null,
                    'mps_types_id' => $mpsTypeId,
                    'm_feret' => $row['m_feret'] ?: null,
                    'identification_date' => $row['identification_date'],
                    'protocols_id' => $protocolId,
                    'laboratories_id' => $laboratoryId,
                    'people_id' => (int) $row['identified_by'],
                    'projects_id' => $projectId,
                ]);

                SubProjectFlag::assign($microplastic, $subProjectId);

                $created++;
            }

            return $created;
        });
    }

    /**
     * @return array<int, string>
     */
    public function eligibleContentTypes(): array
    {
        return [
            HumanSamples::class,
            AnimalSamples::class,
            EnvironmentSamples::class,
            ParasiteSamples::class,
            Pools::class,
        ];
    }

    private function selectedTubesFromOldInput(string $key): Collection
    {
        $ids = array_values(array_filter((array) old($key, [])));

        if ($ids === []) {
            return collect();
        }

        return Tubes::query()
            ->whereIn('id', $ids)
            ->get(['id', 'code', 'alias_code']);
    }

    /**
     * @return array<int, array{id:int,label:string,code:string,alias_code:?string,source_type:string}>
     */
    private function tableTubeOptions(): array
    {
        $tubes = Tubes::query()
            ->where('projects_id', $this->projectId)
            ->whereIn('tubes_content_type', $this->eligibleContentTypes())
            ->orderBy('code')
            ->get(['id', 'code', 'alias_code', 'tubes_content_type']);

        return $tubes->map(function (Tubes $tube): array {
            $label = $tube->code;
            if ($tube->alias_code) {
                $label .= ' ('.$tube->alias_code.')';
            }

            return [
                'id' => (int) $tube->id,
                'label' => $label,
                'code' => (string) $tube->code,
                'alias_code' => $tube->alias_code,
                'source_type' => class_basename((string) $tube->tubes_content_type),
            ];
        })->values()->all();
    }

    public function resolveMpsTypeId(?string $typeName): int
    {
        $normalizedName = trim((string) $typeName);
        if ($normalizedName === '') {
            throw new \RuntimeException('Select a microplastics type.');
        }

        $mpsTypeId = (int) MpsTypes::query()
            ->whereRaw('lower(name) = ?', [strtolower($normalizedName)])
            ->value('id');
        if ($mpsTypeId <= 0) {
            throw new \RuntimeException("Selected microplastics type '{$normalizedName}' was not found.");
        }

        return $mpsTypeId;
    }

    public function resolveOrCreateMpsTypeId(?string $typeName): int
    {
        $normalizedName = trim((string) $typeName);
        if ($normalizedName === '') {
            throw new \RuntimeException('Select a microplastics type.');
        }

        $existingId = (int) MpsTypes::query()
            ->whereRaw('lower(name) = ?', [strtolower($normalizedName)])
            ->value('id');

        if ($existingId > 0) {
            return $existingId;
        }

        return (int) MpsTypes::query()->create([
            'name' => $normalizedName,
        ])->id;
    }

    /**
     * @param  array<int, string>|string|null  $typeNames
     * @return array<int, int>
     */
    public function resolveMpsTypeIds(array|string|null $typeNames): array
    {
        $names = collect(is_array($typeNames) ? $typeNames : [$typeNames])
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            throw new \RuntimeException('Select at least one microplastics type.');
        }

        return $names
            ->map(fn (string $name): int => $this->resolveMpsTypeId($name))
            ->all();
    }

    public function normalizeSourceMeasurementMode(?string $mode): string
    {
        return match (trim((string) $mode)) {
            'pooled' => 'pooled',
            'separate_measurements' => 'separate_measurements',
            default => throw new \RuntimeException('Select how tubes from the same sample source should be handled.'),
        };
    }

    private function nextProjectCode(string $projectCode, string $suffix): string
    {
        $existingCodes = Microplastics::query()
            ->where('projects_id', $this->projectId)
            ->where('code', 'like', $projectCode.'-'.$suffix.'-%')
            ->pluck('code');

        $maxSerial = 0;
        foreach ($existingCodes as $code) {
            if (preg_match('/-'.$suffix.'-(\d+)$/', (string) $code, $matches)) {
                $maxSerial = max($maxSerial, (int) $matches[1]);
            }
        }

        $serial = $maxSerial + 1;

        return $projectCode.'-'.$suffix.'-'.$serial;
    }

    private function nextTubeCode(string $microplasticCode, int $projectId): string
    {
        $existingTubeCodes = Tubes::query()
            ->where('projects_id', $projectId)
            ->where('code', 'like', $microplasticCode.'-%')
            ->pluck('code');

        $maxSerial = 0;
        foreach ($existingTubeCodes as $code) {
            if (preg_match('/-(\d+)$/', (string) $code, $matches)) {
                $maxSerial = max($maxSerial, (int) $matches[1]);
            }
        }

        return $microplasticCode.'-'.($maxSerial + 1);
    }
}
