<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Techniques extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function protocols()
    {
        return $this->hasMany(Protocols::class);
    }

    public function meta_animals()
    {
        return $this->hasMany(MetaAnimal::class);
    }

    public function meta_humans()
    {
        return $this->hasMany(MetaHuman::class);
    }

    public function meta_environments()
    {
        return $this->hasMany(MetaEnvironment::class);
    }

    public function meta_parasites()
    {
        return $this->hasMany(MetaParasite::class);
    }
}
