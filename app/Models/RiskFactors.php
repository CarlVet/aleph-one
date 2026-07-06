<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskFactors extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function meta_animals()
    {
        return $this->morphedByMany(MetaAnimal::class, 'meta', 'risk_factors_meta', 'risk_factors_id', 'meta_id');
    }

    public function meta_humans()
    {
        return $this->morphedByMany(MetaHuman::class, 'meta', 'risk_factors_meta', 'risk_factors_id', 'meta_id');
    }

    public function meta_environments()
    {
        return $this->morphedByMany(MetaEnvironment::class, 'meta', 'risk_factors_meta', 'risk_factors_id', 'meta_id');
    }

    public function meta_parasites()
    {
        return $this->morphedByMany(MetaParasite::class, 'meta', 'risk_factors_meta', 'risk_factors_id', 'meta_id');
    }
}
