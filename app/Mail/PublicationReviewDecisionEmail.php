<?php

namespace App\Mail;

use App\Models\PublicationReviewRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PublicationReviewDecisionEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PublicationReviewRequest $reviewRequest,
        public string $recipientName,
        public string $statusLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Publication review update - '.$this->statusLabel,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.publication-review-decision',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
