<?php

namespace App\Models;

use App\Models\Concerns\HasSubProjectFlag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tubes extends Model
{
    use HasFactory;
    use HasSubProjectFlag;

    protected $guarded = [];

    protected $fillable = [
        'code',
        'alias_code',
        'tubes_content_id',
        'tubes_content_type',
        'tube_type',
        'preservant',
        'purpose',
        'amount',
        'amount_unit',
        'date_processed',
        'projects_id',
        'is_private',
        'state',
    ];

    protected $casts = [
        'date_processed' => 'date',
        'amount' => 'decimal:3',
        'is_private' => 'boolean',
    ];

    public function tubes_content()
    {
        return $this->morphTo();
    }

    public function tube_positions()
    {
        return $this->hasMany(TubePositions::class);
    }

    public function projects()
    {
        return $this->belongsTo(Projects::class);
    }
}
