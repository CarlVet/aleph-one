<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Countries extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function meta_animals()
    {
        return $this->hasMany(MetaAnimal::class);
    }

    public function humans()
    {
        return $this->hasMany(Humans::class);
    }

    public function organizations()
    {
        return $this->hasMany(Organizations::class);
    }

    public function laboratories()
    {
        return $this->hasMany(Laboratories::class);
    }

    public function sampling_sites()
    {
        return $this->hasMany(SamplingSites::class);
    }
}
