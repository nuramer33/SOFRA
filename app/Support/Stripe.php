<?php

namespace App\Support;

use App\Models\Shop;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * A very small Stripe client: Checkout (subscribe), Billing Portal (manage /
 * cancel) and webhook signature checking. It talks to Stripe's REST API with
 * Laravel's HTTP client, so no extra package is needed. Card details are
 * entered on Stripe's own pages and never reach Sofra.
 */
class Stripe
{
    private const API = 'https://api.stripe.com/v1';

    /** Stripe refuses a trial that ends sooner than this. */
    private const MIN_TRIAL_HOURS = 49;

    /** Online payment is on only when the keys and the monthly price are configured. */
    public static function enabled(): bool
    {
        return filled(config('sofra.billing.stripe.secret')) && filled(config('sofra.billing.stripe.price'));
    }

    /**
     * Starts a monthly subscription. The first charge happens when the free
     * period (or the period already paid by hand) ends, so no days are lost.
     *
     * @return array<string, mixed> the Checkout Session (use ['url'])
     */
    public function checkoutSession(Shop $shop, string $email): array
    {
        $trialEnd = $shop->paymentDueAt();

        $payload = [
            'mode' => 'subscription',
            'line_items' => [['price' => config('sofra.billing.stripe.price'), 'quantity' => 1]],
            'client_reference_id' => (string) $shop->id,
            'metadata' => ['shop_id' => (string) $shop->id],
            'subscription_data' => ['metadata' => ['shop_id' => (string) $shop->id]],
            'success_url' => route('billing.show', ['checkout' => 'success']),
            'cancel_url' => route('billing.show', ['checkout' => 'cancelled']),
        ];

        if ($trialEnd !== null && $trialEnd->greaterThan(now()->addHours(self::MIN_TRIAL_HOURS))) {
            $payload['subscription_data']['trial_end'] = $trialEnd->timestamp;
        }

        if ($shop->stripe_customer_id) {
            $payload['customer'] = $shop->stripe_customer_id;
        } else {
            $payload['customer_email'] = $email;
        }

        return $this->post('checkout/sessions', $payload, "checkout-{$shop->id}-".now()->format('YmdHi'));
    }

    /**
     * Stripe's hosted page where the owner updates the card or cancels.
     *
     * @return array<string, mixed>
     */
    public function portalSession(string $customerId): array
    {
        return $this->post('billing_portal/sessions', [
            'customer' => $customerId,
            'return_url' => route('billing.show'),
        ]);
    }

    /**
     * Checks the Stripe-Signature header of a webhook: HMAC-SHA256 of
     * "{timestamp}.{raw body}" with the endpoint secret, and a fresh timestamp.
     */
    public static function validSignature(string $payload, ?string $header, string $secret, int $tolerance = 300): bool
    {
        if ($header === null || $secret === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($key === 't') {
                $timestamp = (int) $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if (! $timestamp || $signatures === [] || abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload, ?string $idempotencyKey = null): array
    {
        $request = $this->http();
        if ($idempotencyKey) {
            $request = $request->withHeaders(['Idempotency-Key' => $idempotencyKey]);
        }

        return $request->post(self::API.'/'.$path, $payload)->throw()->json();
    }

    private function http(): PendingRequest
    {
        return Http::withToken(config('sofra.billing.stripe.secret'))
            ->withHeaders(['Stripe-Version' => config('sofra.billing.stripe.api_version')])
            ->asForm()
            ->acceptJson()
            ->timeout(15);
    }
}
