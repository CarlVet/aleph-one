<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Protocols extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function experiments()
    {
        return $this->hasMany(Experiments::class);
    }

    public function microplastics()
    {
        return $this->hasMany(Microplastics::class);
    }

    public function techniques()
    {
        return $this->belongsTo(Techniques::class);
    }

    public function pathogens()
    {
        return $this->belongsToMany(Pathogens::class)->withTimestamps();
    }

    public function studies()
    {
        return $this->belongsToMany(Studies::class)->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ProtocolComments::class, 'protocols_id');
    }
}
