<?php

namespace App\Models;

use App\Models\Concerns\HasSubProjectFlag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Microplastics extends Model
{
    use HasFactory;
    use HasSubProjectFlag;

    protected $guarded = [];

    protected $casts = [
        'sample_weight' => 'decimal:3',
        'r_coeff' => 'decimal:4',
        'm_feret' => 'decimal:3',
        'identification_date' => 'date',
        'is_private' => 'boolean',
    ];

    public function mps_types()
    {
        return $this->belongsTo(MpsTypes::class);
    }

    public function protocols()
    {
        return $this->belongsTo(Protocols::class);
    }

    public function people()
    {
        return $this->belongsTo(People::class);
    }

    public function microplastics_content()
    {
        return $this->morphTo('microplastics_content');
    }

    public function tubes()
    {
        return $this->morphMany(Tubes::class, 'tubes_content');
    }

    public function projects()
    {
        return $this->belongsTo(Projects::class, 'projects_id');
    }

    public function experiments()
    {
        return $this->morphMany(Experiments::class, 'experiments_content');
    }

    public function laboratories()
    {
        return $this->belongsTo(Laboratories::class);
    }
}
