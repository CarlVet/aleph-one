<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\HumanSampleResource;
use App\Models\HumanSamples;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class HumanSampleController extends ApiController
{
    public function index(): AnonymousResourceCollection
    {
        $samples = QueryBuilder::for(HumanSamples::class)
            ->whereIn('projects_id', $this->userProjectIds())
            ->allowedFilters([
                'code',
                'storage_state',
                AllowedFilter::exact('project_id', 'projects_id'),
                AllowedFilter::exact('sample_type_id', 'sample_types_id'),
            ])
            ->allowedSorts(['code', 'date_collected', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return HumanSampleResource::collection($samples);
    }

    public function show(string $id): HumanSampleResource
    {
        $sample = HumanSamples::query()->whereIn('projects_id', $this->userProjectIds())->findOrFail($id);

        return new HumanSampleResource($sample);
    }
}
