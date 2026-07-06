<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubProjectAssignment extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function subProject()
    {
        return $this->belongsTo(SubProject::class, 'sub_project_id');
    }

    public function assignable()
    {
        return $this->morphTo();
    }
}
