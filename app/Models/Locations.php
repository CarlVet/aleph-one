<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Locations extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function laboratories()
    {
        return $this->belongsTo(Laboratories::class);
    }

    public function human_samples()
    {
        return $this->hasMany(HumanSamples::class);
    }

    public function animal_samples()
    {
        return $this->hasMany(AnimalSamples::class);
    }

    public function environment_samples()
    {
        return $this->hasMany(EnvironmentSamples::class);
    }

    public function parasites()
    {
        return $this->hasMany(Parasites::class);
    }

    public function parasite_samples()
    {
        return $this->hasMany(ParasiteSamples::class);
    }

    public function box_positions()
    {
        return $this->hasMany(BoxPositions::class);
    }
}
