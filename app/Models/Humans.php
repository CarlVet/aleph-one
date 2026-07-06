<?php

namespace App\Models;

use App\Models\Concerns\TracksChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Humans extends Model
{
    use HasFactory, TracksChanges;

    protected $guarded = [];

    /** @var list<string> */
    protected array $activityLogExcept = ['national_id', 'alternate_phone', 'alternate_email', 'national_id_hash'];

    protected function casts(): array
    {
        return [
            'national_id' => 'encrypted',
            'alternate_phone' => 'encrypted',
            'alternate_email' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $human): void {
            if ($human->isDirty('national_id')) {
                $human->national_id_hash = static::blindIndex($human->national_id);
            }
        });
    }

    /**
     * Deterministic keyed hash used as a blind index so an encrypted
     * national_id can still be matched by equality (e.g. import dedup).
     */
    public static function blindIndex(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return hash_hmac('sha256', mb_strtolower($value), (string) config('app.key'));
    }

    public function human_samples()
    {
        return $this->hasMany(HumanSamples::class);
    }

    public function animals()
    {
        return $this->morphMany(Animals::class, 'owner');
    }

    public function organizations()
    {
        return $this->belongsTo(Organizations::class);
    }

    public function projects()
    {
        return $this->belongsTo(Projects::class);
    }

    public function countries()
    {
        return $this->belongsTo(Countries::class);
    }

    public function people()
    {
        return $this->belongsTo(People::class);
    }
}
