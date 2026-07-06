<?php

namespace App\Models;

use App\Models\Concerns\TracksChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectInvitation extends Model
{
    use HasFactory, TracksChanges;

    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function people()
    {
        return $this->belongsTo(People::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Projects::class, 'project_id');
    }
}
