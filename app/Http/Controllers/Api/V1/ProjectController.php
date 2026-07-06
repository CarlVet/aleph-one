<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ProjectResource;
use App\Models\Projects;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProjectController extends ApiController
{
    public function index(): AnonymousResourceCollection
    {
        $projects = QueryBuilder::for(Projects::class)
            ->whereIn('id', $this->userProjectIds())
            ->allowedFilters(['code', 'type', 'status', AllowedFilter::partial('title')])
            ->allowedSorts(['code', 'title', 'date_started', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return ProjectResource::collection($projects);
    }

    public function show(string $id): ProjectResource
    {
        $project = Projects::query()->whereIn('id', $this->userProjectIds())->findOrFail($id);

        return new ProjectResource($project);
    }
}
