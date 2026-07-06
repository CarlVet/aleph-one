<?php

namespace App\Models;

use App\Models\Concerns\HasSubProjectFlag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ParasiteSamples extends Model
{
    use HasFactory;
    use HasSubProjectFlag;

    protected $guarded = [];

    protected $casts = [
        'date_processed' => 'date',
    ];

    public function parasites()
    {
        return $this->belongsTo(Parasites::class);
    }

    public function parasite_sample_types()
    {
        return $this->belongsTo(ParasiteSampleTypes::class);
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

    public function locations()
    {
        return $this->belongsTo(Locations::class);
    }

    public function tubes()
    {
        return $this->morphMany(Tubes::class, 'tubes_content');
    }

    public function people()
    {
        return $this->belongsTo(People::class);
    }

    public function laboratories()
    {
        return $this->belongsTo(Laboratories::class);
    }

    public function projects()
    {
        return $this->belongsTo(Projects::class);
    }

    public function cultures()
    {
        return $this->morphMany(Cultures::class, 'cultures_content');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ParasiteSamplePhoto::class, 'parasite_samples_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(ParasiteSampleObservation::class, 'parasite_samples_id')
            ->orderByDesc('observed_at')
            ->orderByDesc('id');
    }

    public function latestObservation(): HasOne
    {
        return $this->hasOne(ParasiteSampleObservation::class, 'parasite_samples_id')
            ->latestOfMany('observed_at');
    }

    public function latestPhoto(): HasOne
    {
        return $this->hasOne(ParasiteSamplePhoto::class, 'parasite_samples_id')->latestOfMany('id');
    }

    public function syncCoverPhotoPath(): void
    {
        $latest = $this->observations()
            ->with('photo')
            ->orderByDesc('observed_at')
            ->orderByDesc('id')
            ->first();

        $this->update(['photo_path' => $latest?->photo?->photo_path]);
    }
}
