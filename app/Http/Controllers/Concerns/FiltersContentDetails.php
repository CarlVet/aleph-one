<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait FiltersContentDetails
{
    /**
     * Apply a multi-word "AND of OR" filter: every whitespace-separated term must
     * match at least one of the columns/relations defined in the per-term callback.
     * This lets users concatenate words such as "lapalala liver" that live in
     * different underlying columns of an aggregated "content details" column.
     *
     * @param  callable(Builder, string): void  $perTerm
     */
    protected function applyMultiWordFilter(Builder $query, string $value, callable $perTerm): void
    {
        $terms = array_values(array_filter(
            preg_split('/\s+/', trim($value)) ?: [],
            fn (string $term): bool => $term !== ''
        ));

        foreach ($terms as $term) {
            $query->where(function (Builder $group) use ($perTerm, $term): void {
                $perTerm($group, $term);
            });
        }
    }
}
