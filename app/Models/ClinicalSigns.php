<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicalSigns extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function meta_animals()
    {
        return $this->morphedByMany(MetaAnimal::class, 'meta', 'signs_meta', 'clinical_signs_id', 'meta_id');
    }

    public function meta_humans()
    {
        return $this->morphedByMany(MetaHuman::class, 'meta', 'signs_meta', 'clinical_signs_id', 'meta_id');
    }
}
