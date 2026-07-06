<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'updated_at' => 'datetime',
        'visibility_rules' => 'array',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
