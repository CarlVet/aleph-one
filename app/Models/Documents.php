<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documents extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'document_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Projects::class, 'projects_id');
    }

    public function uploaded_by()
    {
        return $this->belongsTo(People::class, 'uploaded_by_id');
    }

    public function parent()
    {
        return $this->belongsTo(Documents::class, 'parent_id');
    }

    public function amendments()
    {
        return $this->hasMany(Documents::class, 'parent_id')
            ->orderByDesc('document_date')
            ->orderByDesc('created_at');
    }
}
