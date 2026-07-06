<?php

namespace App\Support;

use App\Models\AnimalSamples;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\Parasites;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ParasiteOriginDetailsPresenter
{
    /**
     * @param  Collection<int, Parasites>|EloquentCollection<int, Parasites>  $parasites
     */
    public static function hydrate(Collection|EloquentCollection $parasites): void
    {
        $rows = $parasites->filter(fn ($parasite) => $parasite instanceof Parasites)->values();

        if ($rows->isEmpty()) {
            return;
        }

        $collection = $rows instanceof EloquentCollection
            ? $rows
            : new EloquentCollection($rows->all());

        $collection->load('parasites_origin');
    }

    public static function originTypeLabel(?string $originType): string
    {
        return match ($originType) {
            HumanSamples::class => 'Human sample',
            AnimalSamples::class => 'Animal sample',
            EnvironmentSamples::class => 'Environment sample',
            default => $originType ? class_basename($originType) : 'N/A',
        };
    }

    public static function parasiteHref(Parasites $parasite): string
    {
        return '/parasites/'.urlencode((string) $parasite->code);
    }

    public static function originSampleHref(Parasites $parasite): ?string
    {
        $code = $parasite->getAttribute('origin_code_sort');

        if (! $code && $parasite->relationLoaded('parasites_origin')) {
            $origin = $parasite->getRelation('parasites_origin');
            $code = $origin?->code;
        }

        if (! $code) {
            return null;
        }

        $path = match ((string) ($parasite->parasites_origin_type ?? '')) {
            HumanSamples::class => 'humans',
            AnimalSamples::class => 'animals',
            EnvironmentSamples::class => 'environment',
            default => null,
        };

        if (! $path) {
            return null;
        }

        return '/samples/'.$path.'/'.urlencode((string) $code);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public static function rows(Parasites $parasite): array
    {
        $origin = $parasite->relationLoaded('parasites_origin')
            ? $parasite->getRelation('parasites_origin')
            : null;

        if (! $origin instanceof Model) {
            return [
                ['label' => 'Sample type', 'value' => 'N/A'],
                ['label' => 'Sampling site', 'value' => 'N/A'],
                ['label' => 'Date collected', 'value' => 'N/A'],
                ['label' => 'Tube alias', 'value' => 'N/A'],
            ];
        }

        $aliasLabel = self::aliasCodesLabel(self::tubeAliasCodes($origin));

        return match ($origin::class) {
            HumanSamples::class => [
                ['label' => 'Sample type', 'value' => self::relationName($origin, 'sample_types')],
                ['label' => 'Sampling site', 'value' => self::relationName($origin, 'sampling_sites')],
                ['label' => 'Date collected', 'value' => self::formatDate($origin->date_collected ?? null)],
                ['label' => 'Tube alias', 'value' => $aliasLabel],
            ],
            AnimalSamples::class => [
                ['label' => 'Species', 'value' => data_get($origin, 'animals.animal_species.name_common') ?? 'N/A'],
                ['label' => 'Field ID', 'value' => data_get($origin, 'animals.field_label') ?? 'N/A'],
                ['label' => 'Sample type', 'value' => self::relationName($origin, 'sample_types')],
                ['label' => 'Sampling site', 'value' => self::relationName($origin, 'sampling_sites')],
                ['label' => 'Date collected', 'value' => self::formatDate($origin->date_collected ?? null)],
                ['label' => 'Tube alias', 'value' => $aliasLabel],
            ],
            EnvironmentSamples::class => [
                ['label' => 'Env type', 'value' => self::relationName($origin, 'environment_sample_types')],
                ['label' => 'Area', 'value' => $origin->area ?? 'N/A'],
                ['label' => 'Sampling site', 'value' => self::relationName($origin, 'sampling_sites')],
                ['label' => 'Date collected', 'value' => self::formatDate($origin->date_collected ?? null)],
                ['label' => 'Tube alias', 'value' => $aliasLabel],
            ],
            default => [
                ['label' => 'Tube alias', 'value' => $aliasLabel],
            ],
        };
    }

    public static function contentDetailsSearchText(Parasites $parasite): string
    {
        return strtolower(collect(self::rows($parasite))
            ->flatMap(fn (array $row) => [$row['label'], $row['value']])
            ->map(fn ($value) => is_string($value) ? trim($value) : (is_scalar($value) ? (string) $value : ''))
            ->filter(fn (string $value) => $value !== '' && strtolower($value) !== 'n/a')
            ->implode(' '));
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

    private static function formatDate(mixed $value): string
    {
        if (! $value) {
            return 'N/A';
        }

        return Carbon::parse($value)->format('Y-m-d');
    }
}
