<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\AnimalSampleResource;
use App\Models\AnimalSamples;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AnimalSampleController extends ApiController
{
    public function index(): AnonymousResourceCollection
    {
        $samples = QueryBuilder::for(AnimalSamples::class)
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

        return AnimalSampleResource::collection($samples);
    }

    public function show(string $id): AnimalSampleResource
    {
        $sample = AnimalSamples::query()->whereIn('projects_id', $this->userProjectIds())->findOrFail($id);

        return new AnimalSampleResource($sample);
    }
}
