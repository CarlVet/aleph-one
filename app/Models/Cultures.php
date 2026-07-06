<?php

namespace App\Models;

use App\Models\Concerns\HasSubProjectFlag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cultures extends Model
{
    use HasFactory;
    use HasSubProjectFlag;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_cultured' => 'date',
            'date_discarded' => 'date',
            'is_discarded' => 'boolean',
        ];
    }

    // Self-referencing relationships
    public function parent()
    {
        return $this->belongsTo(Cultures::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Cultures::class, 'parent_id');
    }

    public function cultures_content()
    {
        return $this->morphTo();
    }

    public function laboratories()
    {
        return $this->belongsTo(Laboratories::class);
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

    public function pools()
    {
        return $this->morphMany(PoolContents::class, 'samples');
    }

    public function tubes()
    {
        return $this->morphMany(Tubes::class, 'tubes_content');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(CulturePhoto::class, 'cultures_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(CultureObservation::class, 'cultures_id')
            ->orderByDesc('observed_at')
            ->orderByDesc('id');
    }

    public function latestObservation(): HasOne
    {
        return $this->hasOne(CultureObservation::class, 'cultures_id')
            ->latestOfMany('observed_at');
    }

    public function latestPhoto(): HasOne
    {
        return $this->hasOne(CulturePhoto::class, 'cultures_id')->latestOfMany('id');
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
