<?php

namespace App\Models;

use Database\Factories\MpsTypesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MpsTypes extends Model
{
    /** @use HasFactory<MpsTypesFactory> */
    use HasFactory;

    protected $guarded = [];

    public function microplastics()
    {
        return $this->hasMany(Microplastics::class);
    }
}
