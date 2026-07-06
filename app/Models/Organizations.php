<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organizations extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function sampling_sites()
    {
        return $this->hasMany(SamplingSites::class);
    }

    public function laboratories()
    {
        return $this->hasMany(Laboratories::class);
    }

    public function departments()
    {
        return $this->hasMany(Departments::class);
    }

    public function animals()
    {
        return $this->morphMany(Animals::class, 'owner');
    }

    public function countries()
    {
        return $this->belongsTo(Countries::class);
    }
}
