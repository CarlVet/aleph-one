<?php

namespace App\Models;

use App\Models\Concerns\HasSubProjectFlag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NucleicAcids extends Model
{
    use HasFactory;
    use HasSubProjectFlag;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        // Only prevent lazy loading in production
        if (app()->environment('production')) {
            static::preventLazyLoading();
        }
    }

    protected $casts = [
        'date_extracted' => 'date',
    ];

    public function protocols()
    {
        return $this->belongsTo(Protocols::class);
    }

    public function people()
    {
        return $this->belongsTo(People::class);
    }

    public function nucleic_content()
    {
        return $this->morphTo('nucleic_content');
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

    public function pool_contents()
    {
        return $this->morphMany(PoolContents::class, 'samples');
    }

    public function sequences()
    {
        return $this->hasMany(Sequences::class);
    }
}
