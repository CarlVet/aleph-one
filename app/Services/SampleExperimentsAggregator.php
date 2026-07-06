<?php

namespace App\Services;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\Microplastics;
use App\Models\NucleicAcids;
use App\Models\Parasites;
use App\Models\ParasiteSamples;
use App\Models\PoolContents;
use App\Models\Pools;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SampleExperimentsAggregator
{
    /**
     * Per-model relations that lead to derived samples which can carry experiments.
     *
     * @var array<class-string, array<int, string>>
     */
    private array $derivedRelations = [
        AnimalSamples::class => ['nucleic_acids', 'cultures', 'microplastics', 'parasites', 'pools'],
        HumanSamples::class => ['nucleic_acids', 'cultures', 'microplastics', 'parasites', 'pools'],
        ParasiteSamples::class => ['nucleic_acids', 'cultures', 'microplastics', 'pools'],
        EnvironmentSamples::class => ['nucleic_acids', 'cultures', 'microplastics', 'pools'],
        Cultures::class => ['nucleic_acids', 'pools', 'children'],
        Pools::class => ['nucleic_acids', 'microplastics'],
        Parasites::class => ['nucleic_acids', 'cultures', 'pools'],
        NucleicAcids::class => [],
        Microplastics::class => [],
    ];

    /**
     * Every experiment conducted on the given sample AND on any sample derived
     * from it (nucleic acids, cultures, pools, microplastics, parasites and their
     * descendants).
     *
     * @return Collection<int, Experiments>
     */
    public function forSample(Model $sample, ?int $projectId): Collection
    {
        return $this->forNodes([$sample], $projectId);
    }

    /**
     * Every experiment conducted on any of the given root samples/subjects AND on
     * any sample derived from them. Use this for subject profiles (an animal, a
     * patient, a parasite) that own several samples.
     *
     * @param  iterable<int, Model>  $roots
     * @return Collection<int, Experiments>
     */
    public function forNodes(iterable $roots, ?int $projectId): Collection
    {
        $nodes = collect();
        $visited = [];

        foreach ($roots as $root) {
            if ($root instanceof Model) {
                $this->collectNodes($root, $nodes, $visited);
            }
        }

        $idsByType = [];
        foreach ($nodes as $node) {
            $idsByType[$node::class][$node->getKey()] = true;
        }

        if ($idsByType === []) {
            return collect();
        }

        $query = Experiments::query()
            ->with(['protocols', 'pathogens', 'people', 'laboratories']);

        if ($projectId) {
            $query->where('projects_id', $projectId);
        }

        $query->where(function ($outer) use ($idsByType) {
            foreach ($idsByType as $type => $ids) {
                $outer->orWhere(function ($inner) use ($type, $ids) {
                    $inner->where('experiments_content_type', $type)
                        ->whereIn('experiments_content_id', array_keys($ids));
                });
            }
        });

        return $query->orderBy('date_tested')->orderBy('id')->get();
    }

    /**
     * @param  Collection<int, Model>  $nodes
     * @param  array<string, bool>  $visited
     */
    private function collectNodes(Model $model, Collection $nodes, array &$visited): void
    {
        $key = $model::class.'#'.$model->getKey();
        if (isset($visited[$key])) {
            return;
        }
        $visited[$key] = true;

        if (method_exists($model, 'experiments')) {
            $nodes->push($model);
        }

        foreach ($this->derivedRelations[$model::class] ?? [] as $relation) {
            if (! method_exists($model, $relation)) {
                continue;
            }

            // Lazy loading is disabled application-wide, so resolve relations
            // explicitly while traversing the derivation tree.
            $model->loadMissing($relation);

            $related = $model->getRelation($relation);
            if ($related === null) {
                continue;
            }

            $items = $related instanceof EloquentCollection ? $related : collect([$related]);

            foreach ($items as $item) {
                if (! $item instanceof Model) {
                    continue;
                }

                // A sample's pool membership is stored on PoolContents; the actual
                // experiment-bearing node is the Pool it points to.
                if ($item instanceof PoolContents) {
                    $item->loadMissing('pools');
                    $pool = $item->getRelation('pools');
                    if ($pool) {
                        $this->collectNodes($pool, $nodes, $visited);
                    }

                    continue;
                }

                $this->collectNodes($item, $nodes, $visited);
            }
        }
    }
}
