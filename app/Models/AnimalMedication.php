<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnimalMedication extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function animals()
    {
        return $this->belongsTo(Animals::class);
    }

    public function people()
    {
        return $this->belongsTo(People::class, 'prescribed_by');
    }
}
