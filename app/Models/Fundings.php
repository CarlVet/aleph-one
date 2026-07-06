<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fundings extends Model
{
    use HasFactory;

    protected $table = 'fundings';

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function projects()
    {
        return $this->belongsToMany(Projects::class, 'projects_fundings')->withTimestamps();
    }

    public function recipient()
    {
        return $this->belongsTo(People::class, 'recipient_id');
    }
}
