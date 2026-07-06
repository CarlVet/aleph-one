<?php

namespace App\Models;

use App\Models\Concerns\HasSubProjectFlag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoxPositions extends Model
{
    use HasFactory;
    use HasSubProjectFlag;

    protected $guarded = [];

    public function locations()
    {
        return $this->belongsTo(Locations::class);
    }

    public function boxes()
    {
        return $this->belongsTo(Boxes::class);
    }

    public function people()
    {
        return $this->belongsTo(People::class);
    }
}
