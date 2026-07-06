<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProtocolComments extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function protocol(): BelongsTo
    {
        return $this->belongsTo(Protocols::class, 'protocols_id');
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
        return $this->hasMany(self::class, 'parent_id');
    }
}
