<?php

namespace App\Mail;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Tells an owner their subscription is about to end, is overdue or has expired.
 * $stage is "7", "3", "1" (days left), "past_due" or "expired".
 */
class SubscriptionReminder extends Mailable
{
    public function __construct(public Shop $shop, public User $owner, public string $stage, public string $language)
    {
        $this->locale($language);
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->stage) {
            'payment_failed' => __('billing.mail.subject_payment_failed', ['shop' => $this->shop->name]),
            'expired' => __('billing.mail.subject_expired', ['shop' => $this->shop->name]),
            'past_due' => __('billing.mail.subject_past_due', ['shop' => $this->shop->name]),
            default => __('billing.mail.subject_ending', ['shop' => $this->shop->name, 'days' => $this->stage]),
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.subscription-reminder');
    }
}
