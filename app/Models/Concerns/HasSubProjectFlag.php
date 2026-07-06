<?php

namespace App\Models\Concerns;

use App\Models\SubProjectAssignment;

trait HasSubProjectFlag
{
    public function subProjectAssignment()
    {
        return $this->morphOne(SubProjectAssignment::class, 'assignable');
    }

    public function getSubProjectAttribute()
    {
        return $this->subProjectAssignment?->subProject;
    }
}
