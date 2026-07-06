<?php

namespace App\Models;

use Database\Factories\SampleTypesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SampleTypes extends Model
{
    /** @use HasFactory<SampleTypesFactory> */
    use HasFactory;

    protected $guarded = [];

    public function human_samples()
    {
        return $this->hasMany(HumanSamples::class);
    }

    public function animal_samples()
    {
        return $this->hasMany(AnimalSamples::class);
    }

    public function meta_animals()
    {
        return $this->hasMany(MetaAnimal::class);
    }

    public function meta_humans()
    {
        return $this->hasMany(MetaHuman::class);
    }
}
