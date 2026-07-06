<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CultureObservation extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'observed_at' => 'date',
        ];
    }

    public function culture(): BelongsTo
    {
        return $this->belongsTo(Cultures::class, 'cultures_id');
    }

    public function people(): BelongsTo
    {
        return $this->belongsTo(People::class, 'people_id');
    }

    public function photo(): HasOne
    {
        return $this->hasOne(CulturePhoto::class, 'culture_observations_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CultureObservationComment::class, 'culture_observations_id')
            ->whereNull('parent_id')
            ->latest();
    }

    public function allComments(): HasMany
    {
        return $this->hasMany(CultureObservationComment::class, 'culture_observations_id');
    }
}
