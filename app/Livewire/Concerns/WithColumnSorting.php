<?php

namespace App\Livewire\Concerns;

use App\Models\SubProjectAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait WithColumnSorting
{
    public ?string $sortField = null;

    public string $sortDirection = 'asc';

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        if (method_exists($this, 'resetPage')) {
            $pageName = $this->sortingPageName();
            $pageName !== null ? $this->resetPage($pageName) : $this->resetPage();
        }
    }

    protected function sortingPageName(): ?string
    {
        return null;
    }

    /**
     * Apply the active sort to the given query, falling back to a default order.
     *
     * @param  array<string, string|callable>  $map  Column key => column name or callable($query, $direction)
     * @param  string|array{0: string, 1?: string}|callable|null  $default
     */
    protected function applySorting($query, array $map, string|array|callable|null $default = null)
    {
        $field = $this->sortField;

        if ($field !== null && array_key_exists($field, $map)) {
            $direction = strtolower($this->sortDirection) === 'desc' ? 'desc' : 'asc';
            $spec = $map[$field];

            if (is_callable($spec)) {
                $spec($query, $direction);
            } else {
                $query->orderBy($spec, $direction);
            }

            return $query;
        }

        if (is_callable($default)) {
            $default($query);
        } elseif (is_array($default)) {
            $query->orderBy($default[0], $default[1] ?? 'desc');
        } elseif (is_string($default)) {
            $query->orderBy($default, 'desc');
        }

        return $query;
    }

    /**
     * Build a correlated subquery that selects $column by following a chain of
     * belongsTo relations starting from the query's root model. Returns null if
     * any hop is not a belongsTo relation.
     *
     * @param  array<int, string>  $relations  belongsTo relation method names, in order
     */
    protected function relationSortSubquery(Model $root, array $relations, string $column)
    {
        if ($relations === []) {
            return null;
        }

        $first = array_shift($relations);

        if (! method_exists($root, $first)) {
            return null;
        }

        $relation = $root->{$first}();
        if (! $relation instanceof BelongsTo) {
            return null;
        }

        $related = $relation->getRelated();
        $sub = $related->newQuery();
        $sub->whereColumn(
            $related->getTable().'.'.$relation->getOwnerKeyName(),
            $relation->getQualifiedForeignKeyName()
        );

        $previous = $related;
        foreach ($relations as $hop) {
            if (! method_exists($previous, $hop)) {
                return null;
            }

            $next = $previous->{$hop}();
            if (! $next instanceof BelongsTo) {
                return null;
            }

            $nextRelated = $next->getRelated();
            $sub->join(
                $nextRelated->getTable(),
                $previous->getTable().'.'.$next->getForeignKeyName(),
                '=',
                $nextRelated->getTable().'.'.$next->getOwnerKeyName()
            );
            $previous = $nextRelated;
        }

        return $sub->select($previous->getTable().'.'.$column)->limit(1);
    }

    /**
     * Correlated subquery selecting the sub-project code for a model that uses
     * the morphOne subProjectAssignment relation.
     */
    protected function subProjectCodeSortSubquery(Model $root)
    {
        return SubProjectAssignment::query()
            ->join('sub_projects', 'sub_project_assignments.sub_project_id', '=', 'sub_projects.id')
            ->whereColumn('sub_project_assignments.assignable_id', $root->getTable().'.id')
            ->where('sub_project_assignments.assignable_type', $root->getMorphClass())
            ->select('sub_projects.code')
            ->limit(1);
    }

    /**
     * Convenience helper to apply a belongsTo-chain order to a query.
     *
     * @param  array<int, string>  $relations
     */
    protected function orderByRelation($query, array $relations, string $column, string $direction)
    {
        $sub = $this->relationSortSubquery($query->getModel(), $relations, $column);

        if ($sub !== null) {
            $query->orderBy($sub, $direction);
        }

        return $query;
    }
}
