<?php

namespace App\Mail;

use App\Models\Review;
use App\Models\Shop;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Tells a shop owner that a customer left a rating (with the photo attached).
 */
class ReviewReceived extends Mailable
{
    public function __construct(public Review $review, public Shop $shop, public string $language)
    {
        $this->locale($language);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('app.review.mail_subject', ['stars' => str_repeat('★', $this->review->rating), 'shop' => $this->shop->name]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.review-received');
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->review->photo_path === null) {
            return [];
        }

        return [Attachment::fromStorageDisk('public', $this->review->photo_path)->as('review-photo.webp')->withMime('image/webp')];
    }
}
