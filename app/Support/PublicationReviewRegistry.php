<?php

namespace App\Support;

use App\Models\Experiments;
use App\Models\MetaAnimal;
use App\Models\MetaEnvironment;
use App\Models\MetaHuman;
use App\Models\MetaParasite;
use App\Models\Microplastics;
use App\Models\PublicationReviewRequest;
use App\Models\PublicationReviewRequestItem;
use App\Models\Sequences;
use App\Models\Tubes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PublicationReviewRegistry
{
    /**
     * @return class-string<Model>|null
     */
    public static function modelClass(string $dataType, ?string $literatureType = null): ?string
    {
        return match ($dataType) {
            'tubes' => Tubes::class,
            'experiments' => Experiments::class,
            'sequences' => Sequences::class,
            'microplastics' => Microplastics::class,
            'literature' => match ($literatureType) {
                'animal' => MetaAnimal::class,
                'human' => MetaHuman::class,
                'environment' => MetaEnvironment::class,
                'parasite' => MetaParasite::class,
                default => null,
            },
            default => null,
        };
    }

    public static function label(string $dataType, ?string $literatureType = null): string
    {
        return match ($dataType) {
            'tubes' => 'Tubes',
            'experiments' => 'Experiments',
            'sequences' => 'Sequences',
            'microplastics' => 'Microplastics',
            'literature' => match ($literatureType) {
                'animal' => 'Literature / Animal',
                'human' => 'Literature / Human',
                'environment' => 'Literature / Environment',
                'parasite' => 'Literature / Parasite',
                default => 'Literature',
            },
            default => ucfirst(str_replace('_', ' ', $dataType)),
        };
    }

    /**
     * @return array<int, int>
     */
    public static function pendingIds(int $projectId, string $dataType, ?string $literatureType = null): array
    {
        $modelClass = self::modelClass($dataType, $literatureType);
        if ($modelClass === null) {
            return [];
        }

        return PublicationReviewRequestItem::query()
            ->select('publication_review_request_items.reviewable_id')
            ->join('publication_review_requests', 'publication_review_requests.id', '=', 'publication_review_request_items.publication_review_request_id')
            ->where('publication_review_requests.projects_id', $projectId)
            ->where('publication_review_requests.data_type', $dataType)
            ->where('publication_review_requests.status', 'pending')
            ->when($dataType === 'literature', function ($query) use ($literatureType): void {
                $query->where('publication_review_requests.literature_type', $literatureType);
            })
            ->where('publication_review_request_items.reviewable_type', $modelClass)
            ->pluck('publication_review_request_items.reviewable_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Model>  $records
     * @return array<int, array<string, int|string|null>>
     */
    public static function snapshots(Collection $records): array
    {
        return $records
            ->map(function ($record): array {
                return [
                    'reviewable_type' => $record::class,
                    'reviewable_id' => (int) $record->getKey(),
                    'code' => self::recordCode($record),
                    'summary' => self::recordSummary($record),
                ];
            })
            ->values()
            ->all();
    }

    public static function recordCode(object $record): ?string
    {
        $code = data_get($record, 'code');
        if (is_string($code) && trim($code) !== '') {
            return trim($code);
        }

        $refKey = data_get($record, 'studies.ref_key');
        if (is_string($refKey) && trim($refKey) !== '') {
            return trim($refKey);
        }

        $accessionNumber = data_get($record, 'accession_number');
        if (is_string($accessionNumber) && trim($accessionNumber) !== '') {
            return trim($accessionNumber);
        }

        return null;
    }

    public static function recordSummary(object $record): string
    {
        if ($record instanceof Tubes) {
            return trim((string) ($record->purpose ?? 'Tube'));
        }

        if ($record instanceof Experiments) {
            return trim((string) (($record->protocols?->name ?? 'Experiment').' / '.($record->pathogens?->species ?? 'N/A')));
        }

        if ($record instanceof Sequences) {
            return trim((string) (($record->method ?? 'Method N/A').' / '.($record->instrument ?? 'Instrument N/A')));
        }

        if ($record instanceof Microplastics) {
            return trim((string) (($record->mps_types?->name ?? 'MPS type N/A').' / '.($record->protocols?->name ?? 'Protocol N/A')));
        }

        if ($record instanceof MetaAnimal) {
            return trim((string) (($record->animal_species?->name_common ?? 'Animal').' / '.($record->pathogens?->species ?? 'Pathogen N/A')));
        }

        if ($record instanceof MetaHuman) {
            return trim((string) (($record->sample_types?->name ?? 'Human').' / '.($record->pathogens?->species ?? 'Pathogen N/A')));
        }

        if ($record instanceof MetaEnvironment) {
            return trim((string) (($record->environment_sample_types?->name ?? 'Environment').' / '.($record->pathogens?->species ?? 'Pathogen N/A')));
        }

        if ($record instanceof MetaParasite) {
            return trim((string) (($record->parasite_species?->name_scientific ?? 'Parasite').' / '.($record->pathogens?->species ?? 'Pathogen N/A')));
        }

        return class_basename($record);
    }

    public static function publishApprovedItems(PublicationReviewRequest $reviewRequest): int
    {
        $count = 0;

        $reviewRequest->loadMissing('items');

        $reviewRequest->items
            ->groupBy('reviewable_type')
            ->each(function (Collection $items, string $reviewableType) use (&$count): void {
                if (! class_exists($reviewableType)) {
                    return;
                }

                $ids = $items->pluck('reviewable_id')->map(fn ($id) => (int) $id)->values()->all();
                if ($ids === []) {
                    return;
                }

                $updated = $reviewableType::query()
                    ->whereIn('id', $ids)
                    ->where('is_private', true)
                    ->update(['is_private' => false]);

                $count += $updated;
            });

        return $count;
    }
}
