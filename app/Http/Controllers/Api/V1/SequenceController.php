<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\SequenceResource;
use App\Models\Sequences;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SequenceController extends ApiController
{
    public function index(): AnonymousResourceCollection
    {
        $sequences = QueryBuilder::for(Sequences::class)
            ->whereIn('projects_id', $this->userProjectIds())
            ->where($this->excludePrivate(...))
            ->allowedFilters([
                'code',
                'method',
                'accession_number',
                AllowedFilter::exact('project_id', 'projects_id'),
            ])
            ->allowedSorts(['code', 'date_sequenced', 'length', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return SequenceResource::collection($sequences);
    }

    public function show(string $id): SequenceResource
    {
        $sequence = Sequences::query()
            ->whereIn('projects_id', $this->userProjectIds())
            ->where($this->excludePrivate(...))
            ->findOrFail($id);

        return new SequenceResource($sequence);
    }

    private function excludePrivate(Builder $query): void
    {
        $query->where('is_private', false)->orWhereNull('is_private');
    }
}
