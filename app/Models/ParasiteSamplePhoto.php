<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ParasiteSamplePhoto extends Model
{
    protected $guarded = [];

    public function parasiteSample(): BelongsTo
    {
        return $this->belongsTo(ParasiteSamples::class, 'parasite_samples_id');
    }

    public function observation(): BelongsTo
    {
        return $this->belongsTo(ParasiteSampleObservation::class, 'parasite_sample_observations_id');
    }

    public function getObservedAtAttribute(): ?Carbon
    {
        return $this->observation?->observed_at;
    }

    public function getNotesAttribute(): ?string
    {
        return $this->observation?->notes;
    }

    public function getPeopleIdAttribute(): ?int
    {
        return $this->observation?->people_id;
    }

    public function getPeopleAttribute(): ?People
    {
        return $this->observation?->people;
    }

    public function getCommentsAttribute()
    {
        return $this->observation?->comments ?? collect();
    }
}
