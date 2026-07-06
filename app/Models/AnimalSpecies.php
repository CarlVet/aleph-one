<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnimalSpecies extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function animals()
    {
        return $this->hasMany(Animals::class);
    }

    public function meta_animals()
    {
        return $this->hasMany(MetaAnimal::class);
    }
}
