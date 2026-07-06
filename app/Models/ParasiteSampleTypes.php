<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParasiteSampleTypes extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function parasite_samples()
    {
        return $this->hasMany(ParasiteSamples::class);
    }

    public function meta_parasites()
    {
        return $this->hasMany(MetaParasite::class);
    }
}
