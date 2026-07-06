<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationReviewRequestItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function request()
    {
        return $this->belongsTo(PublicationReviewRequest::class, 'publication_review_request_id');
    }

    public function reviewable()
    {
        return $this->morphTo();
    }
}
