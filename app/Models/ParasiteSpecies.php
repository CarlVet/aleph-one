<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParasiteSpecies extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function parasites()
    {
        return $this->hasMany(Parasites::class);
    }

    public function meta_parasites()
    {
        return $this->hasMany(MetaParasite::class);
    }
}
