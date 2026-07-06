<?php

namespace App\Support;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CultureContentDetailsPresenter
{
    /**
     * @param  Collection<int, Cultures>|EloquentCollection<int, Cultures>  $cultures
     */
    public static function hydrate(Collection|EloquentCollection $cultures): void
    {
        $rows = $cultures->filter(fn ($culture) => $culture instanceof Cultures)->values();

        if ($rows->isEmpty()) {
            return;
        }

        $collection = $rows instanceof EloquentCollection
            ? $rows
            : new EloquentCollection($rows->all());

        $collection->load(['parent']);

        $collection->loadMorph('cultures_content', [
            HumanSamples::class => ['humans.countries', 'sample_types', 'sampling_sites', 'tubes'],
            AnimalSamples::class => ['animals.animal_species', 'sample_types', 'sampling_sites', 'tubes'],
            EnvironmentSamples::class => ['environment_sample_types', 'sampling_sites', 'tubes'],
            ParasiteSamples::class => ['parasites.parasite_species', 'parasite_sample_types', 'tubes'],
            NucleicAcids::class => ['tubes', 'protocols'],
            Cultures::class => ['tubes', 'cultures_content', 'parent'],
            Pools::class => ['pool_contents.samples', 'tubes'],
        ]);

        $nestedNucleics = $collection
            ->pluck('cultures_content')
            ->filter(fn ($content) => $content instanceof NucleicAcids)
            ->unique('id')
            ->values();

        if ($nestedNucleics->isNotEmpty()) {
            (new EloquentCollection($nestedNucleics->all()))->loadMorph('nucleic_content', [
                HumanSamples::class => ['tubes', 'sample_types', 'sampling_sites'],
                AnimalSamples::class => ['animals.animal_species', 'tubes', 'sample_types', 'sampling_sites'],
                EnvironmentSamples::class => ['environment_sample_types', 'tubes', 'sampling_sites'],
                ParasiteSamples::class => ['parasites.parasite_species', 'parasite_sample_types', 'tubes'],
                Cultures::class => ['tubes', 'cultures_content', 'parent'],
                Pools::class => ['pool_contents.samples', 'tubes'],
                Experiments::class => ['protocols', 'pathogens', 'tubes'],
            ]);
        }

        $nestedCultures = $collection
            ->pluck('cultures_content')
            ->filter(fn ($content) => $content instanceof Cultures)
            ->merge(
                $nestedNucleics
                    ->pluck('nucleic_content')
                    ->filter(fn ($content) => $content instanceof Cultures)
            )
            ->unique('id')
            ->values();

        if ($nestedCultures->isNotEmpty()) {
            (new EloquentCollection($nestedCultures->all()))->loadMorph('cultures_content', [
                HumanSamples::class => ['tubes'],
                AnimalSamples::class => ['tubes'],
                EnvironmentSamples::class => ['tubes'],
                ParasiteSamples::class => ['tubes'],
                NucleicAcids::class => ['tubes'],
                Cultures::class => ['tubes'],
                Pools::class => ['tubes'],
            ]);
        }
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public static function rows(Cultures $culture): array
    {
        $source = $culture->relationLoaded('cultures_content')
            ? $culture->getRelation('cultures_content')
            : null;

        if (! $source instanceof Model) {
            return [
                ['label' => 'Parent code', 'value' => data_get($culture, 'parent.code') ?? 'N/A'],
                ['label' => 'Content code', 'value' => 'N/A'],
                ['label' => 'Tube alias', 'value' => 'N/A'],
            ];
        }

        $sourceType = $source::class;
        $sourceAliasCodesLabel = self::aliasCodesLabel(self::tubeAliasCodes($source));
        $parentCode = data_get($culture, 'parent.code') ?? 'N/A';

        $rows = match ($sourceType) {
            HumanSamples::class => [
                ['label' => 'Source code', 'value' => $source->code ?? 'N/A'],
                ['label' => 'Sample type', 'value' => self::relationName($source, 'sample_types')],
                ['label' => 'Sampling site', 'value' => self::relationName($source, 'sampling_sites')],
                ['label' => 'Tube alias', 'value' => $sourceAliasCodesLabel],
            ],
            AnimalSamples::class => [
                ['label' => 'Source code', 'value' => $source->code ?? 'N/A'],
                ['label' => 'Species', 'value' => data_get($source, 'animals.animal_species.name_common') ?? 'N/A'],
                ['label' => 'Sample type', 'value' => self::relationName($source, 'sample_types')],
                ['label' => 'Sampling site', 'value' => self::relationName($source, 'sampling_sites')],
                ['label' => 'Tube alias', 'value' => $sourceAliasCodesLabel],
            ],
            EnvironmentSamples::class => [
                ['label' => 'Source code', 'value' => $source->code ?? 'N/A'],
                ['label' => 'Env type', 'value' => self::relationName($source, 'environment_sample_types')],
                ['label' => 'Area', 'value' => $source->area ?? 'N/A'],
                ['label' => 'Tube alias', 'value' => $sourceAliasCodesLabel],
            ],
            ParasiteSamples::class => [
                ['label' => 'Source code', 'value' => $source->code ?? 'N/A'],
                ['label' => 'Species', 'value' => data_get($source, 'parasites.parasite_species.name_scientific') ?? 'N/A'],
                ['label' => 'Sample type', 'value' => self::relationName($source, 'parasite_sample_types')],
                ['label' => 'Tube alias', 'value' => $sourceAliasCodesLabel],
            ],
            Cultures::class => [
                ['label' => 'Source code', 'value' => $source->code ?? 'N/A'],
                ['label' => 'Content type', 'value' => class_basename((string) $source->cultures_content_type) ?: 'N/A'],
                ['label' => 'Content code', 'value' => data_get($source, 'cultures_content.code') ?? 'N/A'],
                ['label' => 'Medium', 'value' => $source->medium ?? 'N/A'],
                ['label' => 'Step', 'value' => $source->step ?? 'N/A'],
                ['label' => 'Tube alias', 'value' => $sourceAliasCodesLabel],
            ],
            Pools::class => [
                ['label' => 'Source code', 'value' => $source->code ?? 'N/A'],
                ['label' => 'Nr pooled', 'value' => $source->nr_pooled !== null ? (string) $source->nr_pooled : 'N/A'],
                ['label' => 'Date pooled', 'value' => $source->date_pooled ? Carbon::parse($source->date_pooled)->format('Y-m-d') : 'N/A'],
                ['label' => 'Tube alias', 'value' => $sourceAliasCodesLabel],
            ],
            NucleicAcids::class => self::nucleicAcidRows($source),
            default => [
                ['label' => 'Source code', 'value' => $source->code ?? 'N/A'],
                ['label' => 'Tube alias', 'value' => $sourceAliasCodesLabel],
            ],
        };

        array_unshift($rows, ['label' => 'Parent code', 'value' => $parentCode]);

        if ($sourceType === Pools::class) {
            $poolContentRows = self::poolContentRows($source);
            if ($poolContentRows !== []) {
                $rows[] = [
                    'label' => 'Pooled contents',
                    'value' => collect($poolContentRows)
                        ->map(fn (array $row) => trim(($row['type'] ?: 'N/A').': '.($row['code'] ?: 'N/A')))
                        ->implode('; '),
                ];
            }
        }

        return $rows;
    }

    public static function contentDetailsSearchText(Cultures $culture): string
    {
        return strtolower(collect(self::rows($culture))
            ->flatMap(fn (array $row) => [$row['label'], $row['value']])
            ->map(fn ($value) => is_string($value) ? trim($value) : (is_scalar($value) ? (string) $value : ''))
            ->filter(fn (string $value) => $value !== '' && strtolower($value) !== 'n/a')
            ->implode(' '));
    }

    public static function daysOnCulture(Cultures $culture): ?float
    {
        if (! $culture->date_cultured) {
            return null;
        }

        $start = Carbon::parse($culture->date_cultured);
        $end = $culture->is_discarded && $culture->date_discarded
            ? Carbon::parse($culture->date_discarded)
            : Carbon::now();

        return round($start->floatDiffInDays($end), 1);
    }

    public static function searchText(Cultures $culture): string
    {
        $parts = [
            $culture->code,
            $culture->type,
            $culture->medium,
            class_basename((string) $culture->cultures_content_type),
            data_get($culture, 'cultures_content.code'),
            data_get($culture, 'parent.code'),
            data_get($culture, 'laboratories.name'),
            $culture->date_cultured ? Carbon::parse($culture->date_cultured)->format('Y-m-d') : null,
        ];

        foreach (self::rows($culture) as $row) {
            $parts[] = $row['label'];
            $parts[] = $row['value'];
        }

        return strtolower(implode(' ', array_filter(array_map(
            fn ($value) => is_string($value) ? trim($value) : (is_scalar($value) ? (string) $value : ''),
            $parts
        ), fn (string $value) => $value !== '')));
    }

    public static function contentTypeLabel(Cultures $culture): string
    {
        $type = (string) ($culture->cultures_content_type ?? '');

        return $type !== '' ? class_basename($type) : 'N/A';
    }

    public static function contentCode(Cultures $culture): ?string
    {
        return data_get($culture, 'cultures_content.code');
    }

    public static function contentHref(Cultures $culture): ?string
    {
        $code = self::contentCode($culture);
        if (! $code) {
            return null;
        }

        return match ((string) ($culture->cultures_content_type ?? '')) {
            HumanSamples::class => '/samples/humans/'.$code,
            AnimalSamples::class => '/samples/animals/'.$code,
            EnvironmentSamples::class => '/samples/environment/'.$code,
            ParasiteSamples::class => '/samples/parasites/'.$code,
            Pools::class => '/samples/pools/'.$code,
            Cultures::class => '/samples/cultures/'.$code,
            NucleicAcids::class => '/samples/nucleic/'.$code,
            default => null,
        };
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private static function nucleicAcidRows(NucleicAcids $nucleic): array
    {
        $nucleicContent = $nucleic->relationLoaded('nucleic_content')
            ? $nucleic->getRelation('nucleic_content')
            : null;

        $rows = [
            ['label' => 'Source code', 'value' => $nucleic->code ?? 'N/A'],
            ['label' => 'NA type', 'value' => $nucleic->type ?? 'N/A'],
            ['label' => 'Protocol', 'value' => self::relationName($nucleic, 'protocols')],
            ['label' => 'Tube alias', 'value' => self::aliasCodesLabel(self::tubeAliasCodes($nucleic))],
        ];

        if ($nucleicContent instanceof Model) {
            $rows[] = ['label' => 'Content type', 'value' => class_basename($nucleicContent::class)];
            $rows[] = ['label' => 'Content code', 'value' => $nucleicContent->code ?? 'N/A'];
        }

        return $rows;
    }

    /**
     * @return array<int, array{type: string, code: string}>
     */
    private static function poolContentRows(Pools $pool): array
    {
        if (! $pool->relationLoaded('pool_contents')) {
            return [];
        }

        return collect($pool->getRelation('pool_contents'))
            ->map(function ($poolContent) {
                $sample = ($poolContent && method_exists($poolContent, 'relationLoaded') && $poolContent->relationLoaded('samples'))
                    ? $poolContent->getRelation('samples')
                    : null;

                return [
                    'type' => class_basename((string) ($poolContent->samples_type ?? '')),
                    'code' => $sample->code ?? 'N/A',
                ];
            })
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function tubeAliasCodes(Model $model): array
    {
        if (! method_exists($model, 'relationLoaded') || ! $model->relationLoaded('tubes')) {
            return [];
        }

        return collect($model->getRelation('tubes'))
            ->pluck('alias_code')
            ->map(fn ($alias) => is_string($alias) ? trim($alias) : '')
            ->filter(fn (string $alias) => $alias !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $aliases
     */
    private static function aliasCodesLabel(array $aliases): string
    {
        return $aliases !== [] ? implode(', ', $aliases) : 'N/A';
    }

    private static function relationName(Model $model, string $relation): string
    {
        if (! method_exists($model, 'relationLoaded') || ! $model->relationLoaded($relation)) {
            return 'N/A';
        }

        $related = $model->getRelation($relation);

        return $related->name ?? 'N/A';
    }
}
