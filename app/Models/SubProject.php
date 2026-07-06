<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubProject extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function project()
    {
        return $this->belongsTo(Projects::class, 'project_id');
    }

    public function people()
    {
        return $this->belongsToMany(People::class, 'sub_project_people')
            ->withTimestamps();
    }

    public function assignments()
    {
        return $this->hasMany(SubProjectAssignment::class);
    }
}
