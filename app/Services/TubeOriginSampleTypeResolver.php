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
use App\Models\Tubes;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

class TubeOriginSampleTypeResolver
{
    public function resolveForTube(Tubes $tube): ?string
    {
        $tube->loadMissing(['tubes_content']);

        $content = $tube->tubes_content;
        if (! $content instanceof Model) {
            return null;
        }

        $this->hydrateTubeContent($tube);

        $contentType = $this->normalizeType((string) $tube->tubes_content_type);

        return match ($contentType) {
            HumanSamples::class, AnimalSamples::class => $this->resolvePrimarySampleType($content),
            EnvironmentSamples::class => $this->resolveEnvironmentSampleType($content),
            ParasiteSamples::class => $this->resolveParasiteSampleType($content),
            NucleicAcids::class => $this->resolveFromNucleicAcid($content),
            Cultures::class => $this->resolveFromCulture($content),
            Pools::class => $this->resolveFromPool($content),
            default => null,
        };
    }

    /**
     * @param  EloquentCollection<int, Tubes>  $tubes
     */
    public function hydrateTubes(EloquentCollection $tubes): void
    {
        if ($tubes->isEmpty()) {
            return;
        }

        $tubes->loadMissing(['tubes_content']);

        $tubes->each(fn (Tubes $tube) => $this->hydrateTubeContent($tube));
    }

    private function hydrateTubeContent(Tubes $tube): void
    {
        $contentType = $this->normalizeType((string) $tube->tubes_content_type);
        $content = $tube->tubes_content;

        if (! $content instanceof Model) {
            return;
        }

        match ($contentType) {
            HumanSamples::class, AnimalSamples::class => $content->loadMissing(['sample_types']),
            EnvironmentSamples::class => $content->loadMissing(['environment_sample_types']),
            ParasiteSamples::class => $content->loadMissing(['parasite_sample_types']),
            NucleicAcids::class => $this->ensureNucleicAcidRelationsLoaded($content),
            Cultures::class => $this->ensureCultureRelationsLoaded($content),
            Pools::class => $this->ensurePoolRelationsLoaded($content),
            default => null,
        };
    }

    private function resolveParasiteSampleType(ParasiteSamples $parasiteSample): ?string
    {
        $parasiteSample->loadMissing(['parasite_sample_types']);

        return $parasiteSample->parasite_sample_types?->name;
    }

    private function resolveFromNucleicAcid(NucleicAcids $nucleicAcid): ?string
    {
        $this->ensureNucleicAcidRelationsLoaded($nucleicAcid);

        return $this->resolveFromPolymorphicContent(
            (string) $nucleicAcid->nucleic_content_type,
            $nucleicAcid->nucleic_content
        );
    }

    private function resolveFromCulture(Cultures $culture): ?string
    {
        $this->ensureCultureRelationsLoaded($culture);

        return $this->resolveFromPolymorphicContent(
            (string) $culture->cultures_content_type,
            $culture->cultures_content
        );
    }

    private function resolveFromPool(Pools $pool): ?string
    {
        $this->ensurePoolRelationsLoaded($pool);

        $labels = $pool->pool_contents
            ->map(function ($content) {
                return $this->resolveFromPolymorphicContent(
                    (string) $content->samples_type,
                    $content->samples
                );
            })
            ->filter()
            ->unique()
            ->values();

        if ($labels->isEmpty()) {
            return null;
        }

        return $labels->take(2)->implode(', ');
    }

    private function resolveFromPolymorphicContent(?string $type, mixed $content): ?string
    {
        if (! $content instanceof Model) {
            return null;
        }

        $normalizedType = $this->normalizeType((string) $type);

        return match ($normalizedType) {
            HumanSamples::class, AnimalSamples::class => $this->resolvePrimarySampleType($content),
            EnvironmentSamples::class => $this->resolveEnvironmentSampleType($content),
            ParasiteSamples::class => $this->resolveParasiteSampleType($content),
            NucleicAcids::class => $this->resolveFromNucleicAcid($content),
            Cultures::class => $this->resolveFromCulture($content),
            Pools::class => $this->resolveFromPool($content),
            Experiments::class => $this->resolveFromExperiment($content),
            default => null,
        };
    }

    private function resolveFromExperiment(Experiments $experiment): ?string
    {
        $this->ensureExperimentRelationsLoaded($experiment);

        return $this->resolveFromPolymorphicContent(
            (string) $experiment->experiments_content_type,
            $experiment->experiments_content
        );
    }

    private function ensureNucleicAcidRelationsLoaded(NucleicAcids $nucleicAcid): void
    {
        $nucleicAcid->loadMissing('nucleic_content');

        (new EloquentCollection([$nucleicAcid]))->loadMorph('nucleic_content', $this->contentMorphRelations());

        $content = $nucleicAcid->nucleic_content;
        if ($content instanceof Cultures) {
            $this->ensureCultureRelationsLoaded($content);
        } elseif ($content instanceof NucleicAcids) {
            $this->ensureNucleicAcidRelationsLoaded($content);
        } elseif ($content instanceof Pools) {
            $this->ensurePoolRelationsLoaded($content);
        } elseif ($content instanceof Experiments) {
            $this->ensureExperimentRelationsLoaded($content);
        }
    }

    private function ensureCultureRelationsLoaded(Cultures $culture): void
    {
        $culture->loadMissing('cultures_content');

        (new EloquentCollection([$culture]))->loadMorph('cultures_content', $this->contentMorphRelations());

        $content = $culture->cultures_content;
        if ($content instanceof Cultures) {
            $this->ensureCultureRelationsLoaded($content);
        } elseif ($content instanceof NucleicAcids) {
            $this->ensureNucleicAcidRelationsLoaded($content);
        } elseif ($content instanceof Pools) {
            $this->ensurePoolRelationsLoaded($content);
        } elseif ($content instanceof Experiments) {
            $this->ensureExperimentRelationsLoaded($content);
        }
    }

    private function ensurePoolRelationsLoaded(Pools $pool): void
    {
        $pool->loadMissing(['pool_contents.samples']);

        if ($pool->pool_contents->isEmpty()) {
            return;
        }

        (new EloquentCollection($pool->pool_contents->all()))->loadMorph('samples', $this->contentMorphRelations());

        foreach ($pool->pool_contents as $poolContent) {
            $sample = $poolContent->samples;
            if ($sample instanceof NucleicAcids) {
                $this->ensureNucleicAcidRelationsLoaded($sample);
            } elseif ($sample instanceof Cultures) {
                $this->ensureCultureRelationsLoaded($sample);
            } elseif ($sample instanceof Pools) {
                $this->ensurePoolRelationsLoaded($sample);
            } elseif ($sample instanceof Experiments) {
                $this->ensureExperimentRelationsLoaded($sample);
            }
        }
    }

    private function ensureExperimentRelationsLoaded(Experiments $experiment): void
    {
        $experiment->loadMissing('experiments_content');

        (new EloquentCollection([$experiment]))->loadMorph('experiments_content', $this->contentMorphRelations());

        $content = $experiment->experiments_content;
        if ($content instanceof NucleicAcids) {
            $this->ensureNucleicAcidRelationsLoaded($content);
        } elseif ($content instanceof Cultures) {
            $this->ensureCultureRelationsLoaded($content);
        } elseif ($content instanceof Pools) {
            $this->ensurePoolRelationsLoaded($content);
        } elseif ($content instanceof Experiments) {
            $this->ensureExperimentRelationsLoaded($content);
        }
    }

    /**
     * @return array<class-string, array<int, string>>
     */
    private function contentMorphRelations(): array
    {
        return [
            HumanSamples::class => ['sample_types'],
            AnimalSamples::class => ['sample_types'],
            EnvironmentSamples::class => ['environment_sample_types'],
            ParasiteSamples::class => ['parasite_sample_types'],
            NucleicAcids::class => ['nucleic_content'],
            Cultures::class => ['cultures_content'],
            Pools::class => ['pool_contents'],
            Experiments::class => ['experiments_content'],
        ];
    }

    private function resolvePrimarySampleType(?Model $sample): ?string
    {
        if ($sample instanceof HumanSamples || $sample instanceof AnimalSamples) {
            $sample->loadMissing('sample_types');

            return $sample->sample_types?->name;
        }

        if ($sample instanceof EnvironmentSamples) {
            return $this->resolveEnvironmentSampleType($sample);
        }

        return null;
    }

    private function resolveEnvironmentSampleType(EnvironmentSamples $sample): ?string
    {
        $sample->loadMissing('environment_sample_types');

        return $sample->environment_sample_types?->name;
    }

    private function normalizeType(string $type): string
    {
        if (! str_contains($type, '\\') && ! str_starts_with($type, 'App\\Models\\')) {
            return "App\\Models\\{$type}";
        }

        if (str_starts_with($type, 'AppModels')) {
            return 'App\\Models\\'.substr($type, strlen('AppModels'));
        }

        return $type;
    }
}
