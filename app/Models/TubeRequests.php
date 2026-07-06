<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TubeRequests extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $fillable = [
        'tubes_id',
        'requester_id',
        'source_project_id',
        'target_project_id',
        'status',
        'request_message',
        'response_message',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function tube()
    {
        return $this->belongsTo(Tubes::class, 'tubes_id');
    }

    public function requester()
    {
        return $this->belongsTo(People::class, 'requester_id');
    }

    public function sourceProject()
    {
        return $this->belongsTo(Projects::class, 'source_project_id');
    }

    public function targetProject()
    {
        return $this->belongsTo(Projects::class, 'target_project_id');
    }
}
