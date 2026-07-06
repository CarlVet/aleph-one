<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoolContents extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function setSamplesTypeAttribute($value)
    {
        // Normalize the class name to ensure it has proper namespace separators
        if (strpos($value, 'AppModels') === 0) {
            $this->attributes['samples_type'] = 'App\\Models\\'.substr($value, 9);
        } else {
            $this->attributes['samples_type'] = $value;
        }
    }

    public function pools()
    {
        return $this->belongsTo(Pools::class);
    }

    public function samples()
    {
        return $this->morphTo();
    }
}
