<?php

namespace App\Services;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\Projects;
use App\Models\Sequences;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectMetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function getMetricsForProject(Projects $project): array
    {
        try {
            $projectId = (int) $project->id;

            $samplesByType = $this->sampleCountsByType($projectId);
            $nucleicBySource = $this->nucleicCountsBySource($projectId);
            $culturesBySource = $this->cultureCountsBySource($projectId);
            $experimentsByContent = $this->experimentCountsByContent($projectId);
            $experimentMetrics = $this->experimentMetrics($projectId);
            $parasiteBySource = $this->parasiteCountsBySource($projectId);

            return [
                'samples' => [
                    'total' => array_sum($samplesByType),
                    'by_type' => $samplesByType,
                    'details' => $this->sampleDetailsByType($projectId),
                ],
                'content' => [
                    'parasite_by_source' => $parasiteBySource,
                    'nucleic_by_source' => $nucleicBySource,
                    'cultures_by_source' => $culturesBySource,
                    'experiments_by_content' => $experimentsByContent,
                ],
                'experiments' => $experimentMetrics,
                'sequences' => Sequences::query()->where('projects_id', $projectId)->count(),
                'documents' => $project->documents()->count(),
                'team_size' => $project->people()->count(),
                'sub_projects' => $project->subProjects()->count(),
            ];
        } catch (\Throwable $e) {
            Log::error('Error fetching project metrics: '.$e->getMessage(), [
                'project_id' => $project->id,
            ]);

            return $this->defaultMetrics();
        }
    }

    /**
     * @return array<string, int>
     */
    private function sampleCountsByType(int $projectId): array
    {
        return [
            'human_samples' => HumanSamples::query()->where('projects_id', $projectId)->count(),
            'animal_samples' => AnimalSamples::query()->where('projects_id', $projectId)->count(),
            'environment_samples' => EnvironmentSamples::query()->where('projects_id', $projectId)->count(),
            'parasite_samples' => ParasiteSamples::query()->where('projects_id', $projectId)->count(),
            'nucleic_acids' => NucleicAcids::query()->where('projects_id', $projectId)->count(),
            'cultures' => Cultures::query()->where('projects_id', $projectId)->count(),
            'pools' => Pools::query()->where('projects_id', $projectId)->count(),
        ];
    }

    /**
     * @return array<string, array<int, array{label: string, data: array<string, int>}>>
     */
    private function sampleDetailsByType(int $projectId): array
    {
        $resolvers = [
            'human_samples' => fn (): array => $this->humanSampleDetails($projectId),
            'animal_samples' => fn (): array => $this->animalSampleDetails($projectId),
            'environment_samples' => fn (): array => $this->environmentSampleDetails($projectId),
            'parasite_samples' => fn (): array => $this->parasiteSampleDetails($projectId),
            'nucleic_acids' => fn (): array => $this->nucleicSampleDetails($projectId),
            'cultures' => fn (): array => $this->cultureSampleDetails($projectId),
            'pools' => fn (): array => $this->poolSampleDetails($projectId),
        ];

        $details = [];

        foreach ($resolvers as $type => $resolver) {
            try {
                $details[$type] = $resolver();
            } catch (\Throwable $e) {
                Log::warning('Error fetching project sample details: '.$e->getMessage(), [
                    'project_id' => $projectId,
                    'sample_type' => $type,
                ]);
                $details[$type] = [];
            }
        }

        return $details;
    }

    /**
     * @return array<int, array{label: string, data: array<string, int>}>
     */
    private function humanSampleDetails(int $projectId): array
    {
        $base = HumanSamples::query()
            ->where('human_samples.projects_id', $projectId)
            ->join('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->leftJoin('sample_types', 'human_samples.sample_types_id', '=', 'sample_types.id');

        return [
            ['label' => 'Sex', 'data' => $this->groupedCount(clone $base, 'humans.sex', 'human_samples.id')],
            ['label' => 'Age range', 'data' => $this->humanAgeRangeCounts(clone $base)],
            ['label' => 'Sample type', 'data' => $this->groupedCount(clone $base, 'sample_types.name', 'human_samples.id')],
        ];
    }

    /**
     * @return array<int, array{label: string, data: array<string, int>}>
     */
    private function animalSampleDetails(int $projectId): array
    {
        $base = AnimalSamples::query()
            ->where('animal_samples.projects_id', $projectId)
            ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id');

        return [
            ['label' => 'Species', 'data' => $this->groupedCount(clone $base, 'animal_species.name_common', 'animal_samples.id')],
            ['label' => 'Sex', 'data' => $this->groupedCount(clone $base, 'animals.sex', 'animal_samples.id')],
            ['label' => 'Age', 'data' => $this->groupedCount(clone $base, 'animals.age', 'animal_samples.id')],
        ];
    }

    /**
     * @return array<int, array{label: string, data: array<string, int>}>
     */
    private function environmentSampleDetails(int $projectId): array
    {
        $base = EnvironmentSamples::query()
            ->where('environment_samples.projects_id', $projectId)
            ->leftJoin('environment_sample_types', 'environment_samples.environment_sample_types_id', '=', 'environment_sample_types.id')
            ->leftJoin('sampling_sites', 'environment_samples.sampling_sites_id', '=', 'sampling_sites.id');

        return [
            ['label' => 'Sample type', 'data' => $this->groupedCount(clone $base, 'environment_sample_types.name', 'environment_samples.id')],
            ['label' => 'Sampling site', 'data' => $this->groupedCount(clone $base, 'sampling_sites.name', 'environment_samples.id')],
        ];
    }

    /**
     * @return array<int, array{label: string, data: array<string, int>}>
     */
    private function parasiteSampleDetails(int $projectId): array
    {
        $base = ParasiteSamples::query()
            ->where('parasite_samples.projects_id', $projectId)
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id');

        return [
            ['label' => 'Species', 'data' => $this->groupedCount(clone $base, 'parasite_species.name_scientific', 'parasite_samples.id')],
            ['label' => 'Stage', 'data' => $this->groupedCount(clone $base, 'parasites.stage', 'parasite_samples.id')],
            ['label' => 'Sex', 'data' => $this->groupedCount(clone $base, 'parasites.sex', 'parasite_samples.id')],
        ];
    }

    /**
     * @return array<int, array{label: string, data: array<string, int>}>
     */
    private function nucleicSampleDetails(int $projectId): array
    {
        $base = NucleicAcids::query()->where('nucleic_acids.projects_id', $projectId);

        return [
            ['label' => 'Nucleic type', 'data' => $this->groupedCount(clone $base, 'nucleic_acids.type', 'nucleic_acids.id')],
            ['label' => 'Source content', 'data' => $this->labelMorphCounts(clone $base, 'nucleic_content_type', 'nucleic_acids.id')],
        ];
    }

    /**
     * @return array<int, array{label: string, data: array<string, int>}>
     */
    private function cultureSampleDetails(int $projectId): array
    {
        $base = Cultures::query()->where('cultures.projects_id', $projectId);

        return [
            ['label' => 'Culture type', 'data' => $this->groupedCount(clone $base, 'cultures.type', 'cultures.id')],
            ['label' => 'Medium', 'data' => $this->groupedCount(clone $base, 'cultures.medium', 'cultures.id')],
            ['label' => 'Source content', 'data' => $this->labelMorphCounts(clone $base, 'cultures_content_type', 'cultures.id')],
        ];
    }

    /**
     * @return array<int, array{label: string, data: array<string, int>}>
     */
    private function poolSampleDetails(int $projectId): array
    {
        $base = Pools::query()->where('pools.projects_id', $projectId);

        return [
            ['label' => 'Pool size', 'data' => $this->groupedCount(clone $base, 'pools.nr_pooled', 'pools.id')],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function parasiteCountsBySource(int $projectId): array
    {
        $rows = ParasiteSamples::query()
            ->where('parasite_samples.projects_id', $projectId)
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->select('parasites.parasites_origin_type as origin', DB::raw('count(*) as total'))
            ->groupBy('parasites.parasites_origin_type')
            ->pluck('total', 'origin')
            ->toArray();

        return [
            'Human' => (int) ($rows[HumanSamples::class] ?? 0),
            'Animal' => (int) ($rows[AnimalSamples::class] ?? 0),
            'Environment' => (int) ($rows[EnvironmentSamples::class] ?? 0),
        ];
    }

    /**
     * @param  Builder<Model>  $query
     * @return array<string, int>
     */
    private function groupedCount($query, string $column, string $countColumn, int $limit = 10): array
    {
        return $query
            ->whereNotNull($column)
            ->select("{$column} as label", DB::raw("count({$countColumn}) as total"))
            ->groupBy($column)
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('total', 'label')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    /**
     * @param  Builder<Model>  $query
     * @return array<string, int>
     */
    private function labelMorphCounts($query, string $typeColumn, string $countColumn, int $limit = 10): array
    {
        $labels = [
            'HumanSamples' => 'Human samples',
            'AnimalSamples' => 'Animal samples',
            'EnvironmentSamples' => 'Environment samples',
            'ParasiteSamples' => 'Parasite samples',
            'NucleicAcids' => 'Nucleic acids',
            'Cultures' => 'Cultures',
            'Pools' => 'Pools',
        ];

        $rows = $query
            ->whereNotNull($typeColumn)
            ->select("{$typeColumn} as raw_type", DB::raw("count({$countColumn}) as total"))
            ->groupBy($typeColumn)
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('total', 'raw_type');

        $distribution = [];
        foreach ($rows as $rawType => $count) {
            $basename = class_basename((string) $rawType);
            $label = $labels[$basename] ?? $basename;
            $distribution[$label] = ($distribution[$label] ?? 0) + (int) $count;
        }

        arsort($distribution);

        return $distribution;
    }

    /**
     * @param  Builder<Model>  $base
     * @return array<string, int>
     */
    private function humanAgeRangeCounts($base): array
    {
        $driver = DB::getDriverName();
        $ageExpr = match ($driver) {
            'mysql' => 'TIMESTAMPDIFF(YEAR, humans.date_of_birth, CURDATE())',
            'pgsql' => "DATE_PART('year', age(CURRENT_DATE, humans.date_of_birth))",
            default => "CAST((julianday('now') - julianday(humans.date_of_birth)) / 365.25 AS INTEGER)",
        };

        $case = "CASE
            WHEN {$ageExpr} < 18 THEN '0-17'
            WHEN {$ageExpr} < 30 THEN '18-29'
            WHEN {$ageExpr} < 50 THEN '30-49'
            WHEN {$ageExpr} < 70 THEN '50-69'
            ELSE '70+'
        END";

        return $base
            ->whereNotNull('humans.date_of_birth')
            ->select(DB::raw("{$case} as bucket"), DB::raw('count(human_samples.id) as total'))
            ->groupBy('bucket')
            ->orderByDesc('total')
            ->pluck('total', 'bucket')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    /**
     * @return array<string, int>
     */
    private function nucleicCountsBySource(int $projectId): array
    {
        $base = NucleicAcids::query()->where('projects_id', $projectId);

        return [
            'Human' => (clone $base)->whereIn('nucleic_content_type', $this->typeVariants(HumanSamples::class))->count(),
            'Animal' => (clone $base)->whereIn('nucleic_content_type', $this->typeVariants(AnimalSamples::class))->count(),
            'Environment' => (clone $base)->whereIn('nucleic_content_type', $this->typeVariants(EnvironmentSamples::class))->count(),
            'Parasite' => (clone $base)->whereIn('nucleic_content_type', $this->typeVariants(ParasiteSamples::class))->count(),
            'Culture' => (clone $base)->whereIn('nucleic_content_type', $this->typeVariants(Cultures::class))->count(),
            'Pool' => (clone $base)->whereIn('nucleic_content_type', $this->typeVariants(Pools::class))->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function cultureCountsBySource(int $projectId): array
    {
        $base = Cultures::query()->where('projects_id', $projectId);

        return [
            'Human' => (clone $base)->whereIn('cultures_content_type', $this->typeVariants(HumanSamples::class))->count(),
            'Animal' => (clone $base)->whereIn('cultures_content_type', $this->typeVariants(AnimalSamples::class))->count(),
            'Environment' => (clone $base)->whereIn('cultures_content_type', $this->typeVariants(EnvironmentSamples::class))->count(),
            'Parasite' => (clone $base)->whereIn('cultures_content_type', $this->typeVariants(ParasiteSamples::class))->count(),
            'Nucleic' => (clone $base)->whereIn('cultures_content_type', $this->typeVariants(NucleicAcids::class))->count(),
            'Pool' => (clone $base)->whereIn('cultures_content_type', $this->typeVariants(Pools::class))->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function experimentCountsByContent(int $projectId): array
    {
        $rows = Experiments::query()
            ->where('projects_id', $projectId)
            ->select('experiments_content_type', DB::raw('count(*) as total'))
            ->whereNotNull('experiments_content_type')
            ->groupBy('experiments_content_type')
            ->pluck('total', 'experiments_content_type');

        $labels = [
            'HumanSamples' => 'Human samples',
            'AnimalSamples' => 'Animal samples',
            'EnvironmentSamples' => 'Environment samples',
            'ParasiteSamples' => 'Parasite samples',
            'NucleicAcids' => 'Nucleic acids',
            'Cultures' => 'Cultures',
            'Pools' => 'Pools',
        ];

        $distribution = [];
        foreach ($rows as $rawType => $count) {
            $basename = class_basename((string) $rawType);
            $label = $labels[$basename] ?? $basename;
            $distribution[$label] = ($distribution[$label] ?? 0) + (int) $count;
        }

        arsort($distribution);

        return $distribution;
    }

    /**
     * @return array<string, mixed>
     */
    private function experimentMetrics(int $projectId): array
    {
        $base = Experiments::query()->where('projects_id', $projectId);
        $total = (clone $base)->count();
        $withPathogen = (clone $base)->whereNotNull('pathogens_id')->count();

        $byOutcome = (clone $base)
            ->select('outcome_discrete', DB::raw('count(*) as total'))
            ->whereNotNull('outcome_discrete')
            ->groupBy('outcome_discrete')
            ->orderByDesc('total')
            ->pluck('total', 'outcome_discrete')
            ->map(fn ($count) => (int) $count)
            ->toArray();

        $topPathogens = (clone $base)
            ->join('pathogens', 'experiments.pathogens_id', '=', 'pathogens.id')
            ->select('pathogens.species', DB::raw('count(*) as total'))
            ->whereNotNull('pathogens.species')
            ->groupBy('pathogens.species')
            ->orderByDesc('total')
            ->limit(4)
            ->pluck('total', 'pathogens.species')
            ->map(fn ($count) => (int) $count)
            ->toArray();

        return [
            'total' => $total,
            'with_pathogen' => $withPathogen,
            'without_pathogen' => max(0, $total - $withPathogen),
            'by_outcome' => $byOutcome,
            'by_pathogen' => $this->hierarchicalPathogenCounts($projectId),
            'top_pathogens' => $topPathogens,
        ];
    }

    /**
     * @return array<string, array{count:int, families:array<string, array{count:int, species:array<string, int>}>}>
     */
    private function hierarchicalPathogenCounts(int $projectId): array
    {
        $rows = Experiments::query()
            ->where('projects_id', $projectId)
            ->whereNotNull('pathogens_id')
            ->select('pathogens_id', DB::raw('count(*) as count'))
            ->groupBy('pathogens_id')
            ->with('pathogens')
            ->get();

        $hierarchicalData = [];

        foreach ($rows as $row) {
            if (! $row->pathogens) {
                continue;
            }

            $domain = $row->pathogens->domain ?? 'Unknown domain';
            $family = $row->pathogens->family ?? 'Unknown family';
            $species = $row->pathogens->species ?? 'Unknown species';
            $count = (int) $row->count;

            if (! isset($hierarchicalData[$domain])) {
                $hierarchicalData[$domain] = ['count' => 0, 'families' => []];
            }

            if (! isset($hierarchicalData[$domain]['families'][$family])) {
                $hierarchicalData[$domain]['families'][$family] = ['count' => 0, 'species' => []];
            }

            $hierarchicalData[$domain]['families'][$family]['species'][$species] =
                ($hierarchicalData[$domain]['families'][$family]['species'][$species] ?? 0) + $count;
            $hierarchicalData[$domain]['families'][$family]['count'] += $count;
            $hierarchicalData[$domain]['count'] += $count;
        }

        foreach ($hierarchicalData as &$domainData) {
            uasort($domainData['families'], fn ($a, $b) => $b['count'] <=> $a['count']);
            foreach ($domainData['families'] as &$familyData) {
                arsort($familyData['species']);
            }
        }
        unset($domainData, $familyData);

        uasort($hierarchicalData, fn ($a, $b) => $b['count'] <=> $a['count']);

        return $hierarchicalData;
    }

    /**
     * @param  class-string  $modelClass
     * @return array<int, string>
     */
    private function typeVariants(string $modelClass): array
    {
        $basename = class_basename($modelClass);

        return array_values(array_unique([
            $modelClass,
            $basename,
            'App\\Models\\'.$basename,
            'App\Models\\'.$basename,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultMetrics(): array
    {
        return [
            'samples' => [
                'total' => 0,
                'by_type' => [
                    'human_samples' => 0,
                    'animal_samples' => 0,
                    'environment_samples' => 0,
                    'parasite_samples' => 0,
                    'nucleic_acids' => 0,
                    'cultures' => 0,
                    'pools' => 0,
                ],
                'details' => [],
            ],
            'content' => [
                'parasite_by_source' => [],
                'nucleic_by_source' => [],
                'cultures_by_source' => [],
                'experiments_by_content' => [],
            ],
            'experiments' => [
                'total' => 0,
                'with_pathogen' => 0,
                'without_pathogen' => 0,
                'by_outcome' => [],
                'by_pathogen' => [],
                'top_pathogens' => [],
            ],
            'sequences' => 0,
            'documents' => 0,
            'team_size' => 0,
            'sub_projects' => 0,
        ];
    }
}
