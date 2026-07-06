<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

abstract class ApiController extends Controller
{
    /**
     * IDs of the projects the authenticated user belongs to.
     * Every v1 resource query is constrained to these projects.
     */
    protected function userProjectIds(): Collection
    {
        return Auth::user()->projects()->pluck('projects.id');
    }

    /**
     * Page size requested by the client, clamped to a sane range.
     */
    protected function perPage(): int
    {
        return (int) min(max(request()->integer('per_page', 25), 1), 100);
    }
}
