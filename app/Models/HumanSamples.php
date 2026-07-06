<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\HasSubProjectFlag;
use App\Models\Concerns\TracksChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HumanSamples extends Model
{
    use HasFactory;
    use HasPublicUuid;
    use HasSubProjectFlag;
    use TracksChanges;

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

    public function humans()
    {
        return $this->belongsTo(Humans::class);
    }

    public function sample_types()
    {
        return $this->belongsTo(SampleTypes::class);
    }

    public function parasites()
    {
        return $this->morphMany(Parasites::class, 'parasites_origin');
    }

    public function sampling_sites()
    {
        return $this->belongsTo(SamplingSites::class);
    }

    public function locations()
    {
        return $this->belongsTo(Locations::class);
    }

    public function people()
    {
        return $this->belongsTo(People::class, 'people_id');
    }

    public function projects()
    {
        return $this->belongsTo(Projects::class);
    }
}
