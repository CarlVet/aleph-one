<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AnimalHealth extends Model
{
    use HasFactory;

    protected $table = 'animal_health';

    protected $guarded = [];

    protected $casts = [
        'check_date' => 'date',
        'alive' => 'boolean',
    ];

    public function animals()
    {
        return $this->belongsTo(Animals::class);
    }

    public function clinical_signs()
    {
        return $this->belongsTo(ClinicalSigns::class);
    }

    public function lesions()
    {
        return $this->belongsTo(Lesions::class);
    }

    public function getClinicalSignsManyAttribute(): Collection
    {
        $sign = $this->relationLoaded('clinical_signs')
            ? $this->getRelation('clinical_signs')
            : $this->clinical_signs;

        return $sign ? collect([$sign]) : collect();
    }

    public function getLesionsManyAttribute(): Collection
    {
        $lesion = $this->relationLoaded('lesions')
            ? $this->getRelation('lesions')
            : $this->lesions;

        return $lesion ? collect([$lesion]) : collect();
    }
}
