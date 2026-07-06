<?php

namespace App\Models;

use App\Models\Concerns\HasSubProjectFlag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pools extends Model
{
    use HasFactory;
    use HasSubProjectFlag;

    protected $guarded = [];

    protected $casts = [
        'date_created' => 'date',
    ];

    public function pool_contents()
    {
        return $this->hasMany(PoolContents::class, 'pools_id');
    }

    public function experiments()
    {
        return $this->morphMany(Experiments::class, 'experiments_content');
    }

    public function microplastics()
    {
        return $this->morphMany(Microplastics::class, 'microplastics_content');
    }

    public function nucleic_acids()
    {
        return $this->morphMany(NucleicAcids::class, 'nucleic_content');
    }

    public function tubes()
    {
        return $this->morphMany(Tubes::class, 'tubes_content');
    }

    public function laboratories()
    {
        return $this->belongsTo(Laboratories::class);
    }

    public function people()
    {
        return $this->belongsTo(People::class);
    }

    public function projects()
    {
        return $this->belongsTo(Projects::class);
    }
}
