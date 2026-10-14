<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionReminder;
use App\Models\Shop;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Emails owners when their subscription is close to ending (7, 3 and 1 days
 * before), overdue, or expired. Each stage is sent once per due date.
 */
#[Signature('sofra:subscription-reminders')]
#[Description('Email shop owners about subscriptions that are ending, overdue or expired')]
class SendSubscriptionReminders extends Command
{
    public function handle(): int
    {
        $sent = 0;

        Shop::query()->where('is_active', true)->with('users')->chunkById(100, function ($shops) use (&$sent) {
            foreach ($shops as $shop) {
                $sent += $this->remind($shop);
            }
        });

        $this->info("Sent {$sent} subscription reminders.");

        return self::SUCCESS;
    }

    private function remind(Shop $shop): int
    {
        $dueAt = $shop->paymentDueAt();
        $stage = $this->stageFor($shop);

        if ($dueAt === null || $stage === null || $shop->slug === config('sofra.demo_slug')) {
            return 0;
        }

        // A shop that renews by itself needs no "please renew" mail before the due date;
        // if the charge fails, the payment-failed email and the overdue stages still apply.
        if ($shop->hasAutoRenew() && ctype_digit($stage)) {
            return 0;
        }

        $notice = $dueAt->toDateString().':'.$stage;
        if ($shop->last_billing_notice === $notice) {
            return 0;
        }

        $count = 0;
        foreach ($shop->users as $owner) {
            try {
                Mail::to($owner->email)->send(new SubscriptionReminder($shop, $owner, $stage, $owner->locale ?? $shop->default_locale));
                $count++;
            } catch (Throwable $e) {
                report($e); // one bad address must not stop the others
            }
        }

        if ($count > 0) {
            $shop->forceFill(['last_billing_notice' => $notice])->save();
        }

        return $count;
    }

    private function stageFor(Shop $shop): ?string
    {
        if ($shop->subscriptionExpired()) {
            return 'expired';
        }

        $days = $shop->daysUntilDue();
        if ($days === null) {
            return null;
        }

        if ($days < 0) {
            return 'past_due';
        }

        $thresholds = collect(config('sofra.billing.reminder_days'))->sort()->values();

        // The smallest reminder window the shop is already inside (e.g. 5 days left → "7").
        $window = $thresholds->first(fn (int $threshold) => $days <= $threshold);

        return $window === null ? null : (string) $window;
    }
}
