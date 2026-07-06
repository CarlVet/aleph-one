<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SamplingSites extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function organization()
    {
        return $this->belongsTo(Organizations::class, 'organizations_id');
    }

    public function countries()
    {
        return $this->belongsTo(Countries::class);
    }

    public function animal_samples()
    {
        return $this->hasMany(AnimalSamples::class);
    }

    public function human_samples()
    {
        return $this->hasMany(HumanSamples::class);
    }

    public function environment_samples()
    {
        return $this->hasMany(EnvironmentSamples::class);
    }
}
