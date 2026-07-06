<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ExperimentResource;
use App\Models\Experiments;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ExperimentController extends ApiController
{
    public function index(): AnonymousResourceCollection
    {
        $experiments = QueryBuilder::for(Experiments::class)
            ->whereIn('projects_id', $this->userProjectIds())
            ->where($this->excludePrivate(...))
            ->allowedFilters([
                'code',
                AllowedFilter::exact('project_id', 'projects_id'),
                AllowedFilter::exact('pathogen_id', 'pathogens_id'),
                AllowedFilter::exact('outcome_binary'),
            ])
            ->allowedSorts(['code', 'date_tested', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return ExperimentResource::collection($experiments);
    }

    public function show(string $id): ExperimentResource
    {
        $experiment = Experiments::query()
            ->whereIn('projects_id', $this->userProjectIds())
            ->where($this->excludePrivate(...))
            ->findOrFail($id);

        return new ExperimentResource($experiment);
    }

    private function excludePrivate(Builder $query): void
    {
        $query->where('is_private', false)->orWhereNull('is_private');
    }
}
