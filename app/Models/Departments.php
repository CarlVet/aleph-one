<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departments extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function organizations()
    {
        return $this->belongsTo(Organizations::class);
    }

    public function people()
    {
        return $this->hasMany(People::class);
    }
}
