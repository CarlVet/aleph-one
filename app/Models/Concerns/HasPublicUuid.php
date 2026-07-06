<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Assigns a globally-unique, immutable public identifier (UUID) on creation.
 *
 * The UUID is a secondary identifier: it never replaces the auto-incrementing
 * primary key or the route key, so existing relations, routes and human-facing
 * internal codes remain untouched. Its purpose is to give every citable record
 * a persistent, globally-unique handle, strengthening FAIR "Findable".
 */
trait HasPublicUuid
{
    protected static function bootHasPublicUuid(): void
    {
        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
