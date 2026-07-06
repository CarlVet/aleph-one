<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\HasSubProjectFlag;
use App\Models\Concerns\TracksChanges;
use Database\Factories\AnimalSamplesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalSamples extends Model
{
    /** @use HasFactory<AnimalSamplesFactory> */
    use HasFactory;

    use HasPublicUuid;
    use HasSubProjectFlag;
    use TracksChanges;

    protected $guarded = [];

    protected $casts = [
        'date_collected' => 'date',
    ];

    public function animals()
    {
        return $this->belongsTo(Animals::class);
    }

    public function sample_types()
    {
        return $this->belongsTo(SampleTypes::class);
    }

    public function sampling_sites()
    {
        return $this->belongsTo(SamplingSites::class);
    }

    public function locations()
    {
        return $this->belongsTo(Locations::class);
    }

    public function people(): BelongsTo
    {
        return $this->belongsTo(People::class, 'people_id');
    }

    public function projects(): BelongsTo
    {
        return $this->belongsTo(Projects::class);
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

    public function pools()
    {
        return $this->morphMany(PoolContents::class, 'samples');
    }

    public function parasites()
    {
        return $this->morphMany(Parasites::class, 'parasites_origin');
    }

    public function tubes()
    {
        return $this->morphMany(Tubes::class, 'tubes_content');
    }

    public function cultures()
    {
        return $this->morphMany(Cultures::class, 'cultures_content');
    }

    /**
     * Update the processed field based on whether the sample has tubes
     */
    public function updateProcessedStatus()
    {
        $this->update(['processed' => $this->tubes()->count() > 0]);
    }
}
