<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationReviewRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Projects::class, 'projects_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function items()
    {
        return $this->hasMany(PublicationReviewRequestItem::class);
    }
}
