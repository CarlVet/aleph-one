<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesions extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function meta_animals()
    {
        return $this->morphedByMany(MetaAnimal::class, 'meta', 'lesions_meta', 'lesions_id', 'meta_id');
    }

    public function meta_humans()
    {
        return $this->morphedByMany(MetaHuman::class, 'meta', 'lesions_meta', 'lesions_id', 'meta_id');
    }
}
