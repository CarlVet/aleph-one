<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParasiteSampleObservationComment extends Model
{
    protected $guarded = [];

    public function observation(): BelongsTo
    {
        return $this->belongsTo(ParasiteSampleObservation::class, 'parasite_sample_observations_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->oldest();
    }
}
