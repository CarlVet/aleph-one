<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Boxes extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function tube_positions()
    {
        return $this->hasMany(TubePositions::class);
    }

    public function box_positions()
    {
        return $this->hasMany(BoxPositions::class);
    }

    public function latest_box_position()
    {
        return $this->hasOne(BoxPositions::class)->ofMany([
            'date_moved' => 'max',
            'id' => 'max',
        ]);
    }

    public function projects()
    {
        return $this->belongsTo(Projects::class);
    }
}
