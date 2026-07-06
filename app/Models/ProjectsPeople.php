<?php

namespace App\Models;

use App\Models\Concerns\TracksChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectsPeople extends Model
{
    use HasFactory, TracksChanges;

    protected $guarded = [];

    public function projects()
    {
        return $this->belongsTo(Projects::class, 'projects_id');
    }

    public function people()
    {
        return $this->belongsTo(People::class, 'people_id');
    }
}
