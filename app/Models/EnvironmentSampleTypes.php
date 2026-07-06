<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnvironmentSampleTypes extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function environment_samples()
    {
        return $this->hasMany(EnvironmentSamples::class);
    }

    public function meta_environments()
    {
        return $this->hasMany(MetaEnvironment::class);
    }
}
