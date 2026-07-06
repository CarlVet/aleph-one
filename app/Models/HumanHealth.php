<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HumanHealth extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function human()
    {
        return $this->belongsTo(Humans::class);
    }

    public function lesions()
    {
        return $this->hasMany(Lesions::class);
    }

    public function symptoms()
    {
        return $this->hasMany(ClinicalSigns::class);
    }
}
