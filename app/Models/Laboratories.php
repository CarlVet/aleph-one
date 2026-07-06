<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Laboratories extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organizations::class);
    }

    public function countries()
    {
        return $this->belongsTo(Countries::class);
    }

    public function locations()
    {
        return $this->hasMany(Locations::class);
    }

    public function parasites()
    {
        return $this->hasMany(Parasites::class);
    }

    public function nucleic_acids()
    {
        return $this->hasMany(NucleicAcids::class);
    }

    public function microplastics()
    {
        return $this->hasMany(Microplastics::class);
    }

    public function sequences()
    {
        return $this->hasMany(Sequences::class);
    }

    public function cultures()
    {
        return $this->hasMany(Cultures::class);
    }

    public function pools()
    {
        return $this->hasMany(Pools::class);
    }

    public function experiments()
    {
        return $this->hasMany(Experiments::class);
    }
}
