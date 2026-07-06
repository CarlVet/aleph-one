<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class CulturePhoto extends Model
{
    protected $guarded = [];

    public function culture(): BelongsTo
    {
        return $this->belongsTo(Cultures::class, 'cultures_id');
    }

    public function observation(): BelongsTo
    {
        return $this->belongsTo(CultureObservation::class, 'culture_observations_id');
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
