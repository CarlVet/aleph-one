<?php

namespace App\Support;

use App\Models\AnimalSpecies;
use App\Models\Boxes;
use App\Models\Laboratories;
use App\Models\Locations;
use App\Models\ParasiteSpecies;
use App\Models\Pathogens;
use App\Models\Protocols;
use App\Models\SamplingSites;
use Illuminate\Support\Collection;

class LookupTableData
{
    /**
     * @param  Collection<int, Boxes>|null  $boxes
     * @return list<array<string, mixed>>
     */
    public static function boxes(?Collection $boxes = null): array
    {
        $collection = $boxes ?? Boxes::query()->orderBy('code')->get();

        return $collection
            ->map(function (Boxes $box): array {
                $code = (string) ($box->code ?? '');
                $name = (string) ($box->name ?? '');
                $contentType = (string) ($box->dynamic_content_type ?? $box->content_type ?? 'Empty');
                $rows = (int) ($box->n_rows ?? 0);
                $columns = (int) ($box->n_columns ?? 0);
                $label = $code
                    .($name !== '' ? ' - '.$name : '')
                    .' ('.$rows.'x'.$columns.') - '.$contentType;

                return [
                    'id' => (string) $box->id,
                    'code' => $code,
                    'name' => $name,
                    'alias_code' => (string) ($box->alias_code ?? ''),
                    'content_type' => $contentType,
                    'dimensions' => $rows.'x'.$columns,
                    'n_rows' => $rows,
                    'n_columns' => $columns,
                    'label' => $label,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function locations(): array
    {
        return Locations::query()
            ->with('laboratories.countries')
            ->orderBy('name')
            ->get()
            ->map(function (Locations $location): array {
                return [
                    'id' => (string) $location->id,
                    'name' => (string) ($location->name ?? ''),
                    'type' => (string) ($location->type ?? ''),
                    'room' => (string) ($location->room ?? ''),
                    'barcode' => (string) ($location->barcode ?? ''),
                    'laboratory' => (string) ($location->laboratories?->name ?? ''),
                    'country' => (string) ($location->laboratories?->countries?->name ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function samplingSites(): array
    {
        return SamplingSites::query()
            ->with(['countries', 'organization'])
            ->orderBy('name')
            ->get()
            ->map(function (SamplingSites $site): array {
                return [
                    'id' => (string) $site->id,
                    'name' => (string) ($site->name ?? ''),
                    'site_type' => (string) ($site->site_type ?? ''),
                    'country' => (string) ($site->countries?->name ?? ''),
                    'region' => (string) ($site->region ?? ''),
                    'city' => (string) ($site->city ?? ''),
                    'province' => (string) ($site->province ?? ''),
                    'organization' => (string) ($site->organization?->name ?? ''),
                    'latitude' => $site->latitude,
                    'longitude' => $site->longitude,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function laboratories(): array
    {
        return Laboratories::query()
            ->with('countries')
            ->orderBy('name')
            ->get()
            ->map(function (Laboratories $laboratory): array {
                return [
                    'id' => (string) $laboratory->id,
                    'name' => (string) ($laboratory->name ?? ''),
                    'lab_type' => (string) ($laboratory->lab_type ?? ''),
                    'country' => (string) ($laboratory->countries?->name ?? ''),
                    'city' => (string) ($laboratory->city ?? ''),
                    'address' => (string) ($laboratory->address ?? ''),
                    'latitude' => $laboratory->latitude,
                    'longitude' => $laboratory->longitude,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Protocols>|null  $protocols
     * @return list<array<string, mixed>>
     */
    public static function protocols(?Collection $protocols = null): array
    {
        $collection = $protocols ?? Protocols::query()
            ->with(['techniques', 'pathogens', 'experiments.projects', 'microplastics.projects'])
            ->orderBy('name')
            ->get();

        return $collection
            ->map(function (Protocols $protocol): array {
                return [
                    'id' => (string) $protocol->id,
                    'code' => (string) ($protocol->code ?? ''),
                    'name' => (string) ($protocol->name ?? ''),
                    'technique_name' => (string) ($protocol->techniques?->name ?? ''),
                    'technique_type' => (string) ($protocol->techniques?->type ?? ''),
                    'target_pathogens' => $protocol->pathogens
                        ->pluck('species')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                    'project_codes' => $protocol->relationLoaded('experiments')
                        ? $protocol->experiments
                            ->pluck('projects.code')
                            ->merge($protocol->relationLoaded('microplastics')
                                ? $protocol->microplastics->pluck('projects.code')
                                : collect())
                            ->filter()
                            ->unique()
                            ->values()
                            ->all()
                        : [],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function parasiteSpecies(): array
    {
        return ParasiteSpecies::query()
            ->orderBy('name_scientific')
            ->get()
            ->map(function (ParasiteSpecies $species): array {
                return [
                    'id' => (string) $species->id,
                    'name_scientific' => (string) ($species->name_scientific ?? ''),
                    'name_common' => (string) ($species->name_common ?? ''),
                    'genus' => (string) ($species->genus ?? ''),
                    'family' => (string) ($species->family ?? ''),
                    'order' => (string) ($species->order ?? ''),
                    'class' => (string) ($species->class ?? ''),
                    'phylum' => (string) ($species->phylum ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function animalSpecies(): array
    {
        return AnimalSpecies::query()
            ->orderBy('name_common')
            ->get()
            ->map(function (AnimalSpecies $species): array {
                $common = (string) ($species->name_common ?? '');
                $scientific = (string) ($species->name_scientific ?? '');

                return [
                    'id' => (string) $species->id,
                    'name_common' => $common,
                    'name_scientific' => $scientific,
                    'label' => trim($common.($scientific !== '' ? ' ('.$scientific.')' : '')),
                    'genus' => (string) ($species->genus ?? ''),
                    'family' => (string) ($species->family ?? ''),
                    'order' => (string) ($species->order ?? ''),
                    'class' => (string) ($species->class ?? ''),
                    'phylum' => (string) ($species->phylum ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function pathogens(): array
    {
        return Pathogens::query()
            ->orderBy('species')
            ->get()
            ->map(function (Pathogens $pathogen): array {
                return [
                    'id' => (string) $pathogen->id,
                    'species' => (string) ($pathogen->species ?? ''),
                    'genus' => (string) ($pathogen->genus ?? ''),
                    'family' => (string) ($pathogen->family ?? ''),
                    'order' => (string) ($pathogen->order ?? ''),
                    'class' => (string) ($pathogen->class ?? ''),
                    'phylum' => (string) ($pathogen->phylum ?? ''),
                ];
            })
            ->values()
            ->all();
    }
}
