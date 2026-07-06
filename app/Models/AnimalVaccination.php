<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnimalVaccination extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date_administered' => 'date',
        'next_due_date' => 'date',
    ];

    public function animals()
    {
        return $this->belongsTo(Animals::class);
    }

    public function people()
    {
        return $this->belongsTo(People::class, 'administered_by');
    }
}
