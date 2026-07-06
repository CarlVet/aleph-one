<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnimalMovement extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date_moved' => 'date',
        'coordinates_start_lat' => 'decimal:8',
        'coordinates_start_lng' => 'decimal:8',
        'coordinates_destination_lat' => 'decimal:8',
        'coordinates_destination_lng' => 'decimal:8',
    ];

    public function animals()
    {
        return $this->belongsTo(Animals::class);
    }

    public function source_sampling_site()
    {
        return $this->belongsTo(SamplingSites::class, 'source_sampling_site_id');
    }

    public function destination_sampling_site()
    {
        return $this->belongsTo(SamplingSites::class, 'destination_sampling_site_id');
    }
}
