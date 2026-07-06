<?php

namespace App\Models;

use App\Models\Concerns\TracksChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Animals extends Model
{
    use HasFactory, TracksChanges;

    protected $table = 'animals';

    protected $guarded = [];

    public function animal_species()
    {
        return $this->belongsTo(AnimalSpecies::class);
    }

    public function animal_samples()
    {
        return $this->hasMany(AnimalSamples::class);
    }

    public function animal_health()
    {
        return $this->hasMany(AnimalHealth::class);
    }

    public function animal_vaccinations()
    {
        return $this->hasMany(AnimalVaccination::class);
    }

    public function animal_medications()
    {
        return $this->hasMany(AnimalMedication::class);
    }

    public function parasites()
    {
        return $this->hasMany(Parasites::class);
    }

    public function owner()
    {
        return $this->morphTo();
    }

    public function projects()
    {
        return $this->belongsTo(Projects::class);
    }

    public function animal_movements()
    {
        return $this->hasMany(AnimalMovement::class);
    }

    public function latest_movement(): HasOne
    {
        return $this->hasOne(AnimalMovement::class)->latestOfMany('date_moved');
    }
}
