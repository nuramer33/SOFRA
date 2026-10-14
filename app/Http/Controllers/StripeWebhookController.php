<?php

namespace App\Http\Controllers;

use App\Mail\SubscriptionReminder;
use App\Models\Shop;
use App\Models\SubscriptionEvent;
use App\Support\Stripe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Stripe tells us what happened: a subscription started, a month was paid, a
 * charge failed, a subscription ended. This is the ONLY place that grants paid
 * time from online payments, and only after the signature is verified: the
 * page the customer returns to after paying proves nothing.
 *
 * Excluded from CSRF (Stripe cannot send a token); the signature replaces it.
 * Every event id is processed once.
 */
class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('sofra.billing.stripe.webhook_secret');

        if (! Stripe::validSignature($request->getContent(), $request->header('Stripe-Signature'), $secret)) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        $event = json_decode($request->getContent(), true);
        if (! is_array($event) || ! isset($event['id'], $event['type'], $event['data']['object'])) {
            return response()->json(['message' => 'Malformed event.'], 400);
        }

        // Insert + handle in one transaction: a failure rolls the id back so Stripe's retry runs again.
        DB::transaction(function () use ($event) {
            $new = DB::table('stripe_events')->insertOrIgnore(['id' => $event['id'], 'type' => $event['type'], 'processed_at' => now()]);

            if ($new) {
                $this->handle($event['type'], $event['data']['object']);
            }
        });

        return response()->json(['received' => true]);
    }

    /**
     * @param  array<string, mixed>  $object
     */
    private function handle(string $type, array $object): void
    {
        match ($type) {
            'checkout.session.completed' => $this->subscriptionStarted($object),
            'invoice.paid', 'invoice.payment_succeeded' => $this->invoicePaid($object),
            'invoice.payment_failed' => $this->paymentFailed($object),
            'customer.subscription.updated' => $this->subscriptionUpdated($object),
            'customer.subscription.deleted' => $this->subscriptionEnded($object),
            default => null, // events we do not need
        };
    }

    /** The owner finished Checkout: remember the Stripe ids and turn on auto-renew. */
    private function subscriptionStarted(array $session): void
    {
        if (($session['mode'] ?? null) !== 'subscription') {
            return;
        }

        $shop = Shop::find((int) ($session['client_reference_id'] ?? $session['metadata']['shop_id'] ?? 0));
        if ($shop === null) {
            report(new \RuntimeException('Stripe checkout for an unknown shop: '.($session['id'] ?? '?')));

            return;
        }

        $shop->forceFill([
            'stripe_customer_id' => $session['customer'] ?? $shop->stripe_customer_id,
            'stripe_subscription_id' => $session['subscription'] ?? null,
            'auto_renew' => true,
        ])->save();

        $this->log($shop, SubscriptionEvent::SUBSCRIBED);
    }

    /** A month was really charged: the paid period now runs to the end of that month. */
    private function invoicePaid(array $invoice): void
    {
        // The free-trial invoice is 0: nothing was paid, so no time is granted.
        if ((int) ($invoice['amount_paid'] ?? 0) <= 0) {
            return;
        }

        $shop = $this->shopFor($invoice);
        if ($shop === null) {
            return;
        }

        $end = $invoice['lines']['data'][0]['period']['end'] ?? null;
        // In the app's time zone: a Carbon in another zone would be saved as that zone's wall clock.
        $shop->recordOnlinePayment($end ? Carbon::createFromTimestamp((int) $end, config('app.timezone')) : now()->addMonth());

        $this->log($shop, SubscriptionEvent::ONLINE_PAYMENT, 1);
    }

    /** The card was declined: flag it and tell the owner once, with a link to fix it. */
    private function paymentFailed(array $invoice): void
    {
        $shop = $this->shopFor($invoice);
        if ($shop === null) {
            return;
        }

        $firstFailure = $shop->payment_failed_at === null;
        $shop->forceFill(['payment_failed_at' => now()])->save();

        $this->log($shop, SubscriptionEvent::PAYMENT_FAILED);

        if ($firstFailure) {
            foreach ($shop->users as $owner) {
                try {
                    Mail::to($owner->email)->send(new SubscriptionReminder($shop, $owner, 'payment_failed', $owner->locale ?? $shop->default_locale));
                } catch (Throwable $e) {
                    report($e); // a mail problem must not make Stripe retry the whole event
                }
            }
        }
    }

    /** Cancelled at period end (or resumed) in the Stripe portal. */
    private function subscriptionUpdated(array $subscription): void
    {
        $shop = $this->shopFor($subscription);
        if ($shop === null) {
            return;
        }

        $renews = in_array($subscription['status'] ?? '', ['active', 'trialing', 'past_due'], true)
            && ! ($subscription['cancel_at_period_end'] ?? false);

        $shop->forceFill(['auto_renew' => $renews, 'stripe_subscription_id' => $subscription['id'] ?? $shop->stripe_subscription_id])->save();
    }

    /** The subscription is over. The paid time already bought stays until paid_until. */
    private function subscriptionEnded(array $subscription): void
    {
        $shop = $this->shopFor($subscription);
        if ($shop === null) {
            return;
        }

        $shop->forceFill(['auto_renew' => false, 'stripe_subscription_id' => null])->save();

        $this->log($shop, SubscriptionEvent::CANCELLED);
    }

    /**
     * @param  array<string, mixed>  $object  an invoice or subscription
     */
    private function shopFor(array $object): ?Shop
    {
        $shop = filled($object['customer'] ?? null) ? Shop::where('stripe_customer_id', $object['customer'])->first() : null;

        $shopId = $object['metadata']['shop_id'] ?? $object['subscription_details']['metadata']['shop_id'] ?? null;
        $shop ??= filled($shopId) ? Shop::find((int) $shopId) : null;

        if ($shop === null) {
            report(new \RuntimeException('Stripe event for an unknown shop (customer '.($object['customer'] ?? '?').').'));

            return null;
        }

        // Events can arrive out of order: remember the customer as soon as we know whose it is.
        if (blank($shop->stripe_customer_id) && filled($object['customer'] ?? null)) {
            $shop->forceFill(['stripe_customer_id' => $object['customer']])->save();
        }

        return $shop;
    }

    private function log(Shop $shop, string $type, ?int $amount = null): void
    {
        SubscriptionEvent::create(['shop_id' => $shop->id, 'user_id' => null, 'type' => $type, 'amount' => $amount]);
    }
}
