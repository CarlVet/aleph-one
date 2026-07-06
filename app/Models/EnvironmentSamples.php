<?php

namespace App\Models;

use App\Models\Concerns\HasSubProjectFlag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnvironmentSamples extends Model
{
    use HasFactory;
    use HasSubProjectFlag;

    protected $guarded = [];

    protected $casts = [
        'date_collected' => 'date',
    ];

    public function cultures()
    {
        return $this->morphMany(Cultures::class, 'cultures_content');
    }

    public function pools()
    {
        return $this->morphMany(PoolContents::class, 'samples');
    }

    public function tubes()
    {
        return $this->morphMany(Tubes::class, 'tubes_content');
    }

    public function parasites()
    {
        return $this->morphMany(Parasites::class, 'parasites_origin');
    }

    public function nucleic_acids()
    {
        return $this->morphMany(NucleicAcids::class, 'nucleic_content');
    }

    public function experiments()
    {
        return $this->morphMany(Experiments::class, 'experiments_content');
    }

    public function microplastics()
    {
        return $this->morphMany(Microplastics::class, 'microplastics_content');
    }

    public function sampling_sites()
    {
        return $this->belongsTo(SamplingSites::class);
    }

    public function people()
    {
        return $this->belongsTo(People::class, 'people_id');
    }

    public function projects()
    {
        return $this->belongsTo(Projects::class);
    }

    public function environment_sample_types()
    {
        return $this->belongsTo(EnvironmentSampleTypes::class);
    }

    public function locations()
    {
        return $this->belongsTo(Locations::class);
    }
}
