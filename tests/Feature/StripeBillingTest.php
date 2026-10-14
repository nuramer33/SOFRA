<?php

namespace Tests\Feature;

use App\Mail\SubscriptionReminder;
use App\Models\Shop;
use App\Models\SubscriptionEvent;
use App\Models\User;
use App\Support\Stripe;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StripeBillingTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const SECRET = 'whsec_test_secret';

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'sofra.billing.stripe.secret' => 'sk_test_123',
            'sofra.billing.stripe.price' => 'price_monthly',
            'sofra.billing.stripe.webhook_secret' => self::SECRET,
        ]);

        $this->freezeSecond();
        $this->shop = Shop::factory()->create(['slug' => 'kahve-evi', 'trial_ends_at' => now()->addDays(30)]);
    }

    /** POSTs a signed webhook exactly the way Stripe does. */
    private function webhook(string $type, array $object, ?string $id = null, ?string $secret = self::SECRET, ?int $timestamp = null)
    {
        $payload = json_encode(['id' => $id ?? 'evt_'.bin2hex(random_bytes(6)), 'type' => $type, 'data' => ['object' => $object]]);
        $timestamp ??= time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, (string) $secret);

        return $this->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ], $payload);
    }

    private function invoice(array $overrides = []): array
    {
        return [
            'customer' => 'cus_1', 'subscription' => 'sub_1', 'amount_paid' => 1200,
            'lines' => ['data' => [['period' => ['end' => now()->addDays(60)->timestamp]]]],
            'subscription_details' => ['metadata' => ['shop_id' => (string) $this->shop->id]],
            ...$overrides,
        ];
    }

    private function link(): void
    {
        $this->shop->forceFill(['stripe_customer_id' => 'cus_1', 'stripe_subscription_id' => 'sub_1', 'auto_renew' => true])->save();
    }

    // ---- Webhook security ------------------------------------------------------

    public function test_a_webhook_without_a_valid_signature_is_rejected_and_changes_nothing(): void
    {
        $this->link();

        $this->webhook('invoice.paid', $this->invoice(), secret: 'whsec_wrong')->assertStatus(400);
        $this->postJson('/stripe/webhook', ['id' => 'evt_x', 'type' => 'invoice.paid', 'data' => ['object' => $this->invoice()]])->assertStatus(400);
        $this->webhook('invoice.paid', $this->invoice(), timestamp: time() - 3600)->assertStatus(400); // too old: replay

        $this->assertNull($this->shop->refresh()->paid_until);
        $this->assertSame(0, \DB::table('stripe_events')->count());
    }

    public function test_the_webhook_is_closed_when_no_secret_is_configured(): void
    {
        config(['sofra.billing.stripe.webhook_secret' => null]);

        $this->webhook('invoice.paid', $this->invoice(), secret: '')->assertStatus(400);
    }

    public function test_signature_checking_matches_stripes_scheme(): void
    {
        $body = '{"a":1}';
        $t = time();
        $good = hash_hmac('sha256', "{$t}.{$body}", 'k');

        $this->assertTrue(Stripe::validSignature($body, "t={$t},v1=bad,v1={$good}", 'k'));
        $this->assertFalse(Stripe::validSignature($body.' ', "t={$t},v1={$good}", 'k'));
        $this->assertFalse(Stripe::validSignature($body, null, 'k'));
        $this->assertFalse(Stripe::validSignature($body, "t={$t}", 'k'));
    }

    // ---- Events ---------------------------------------------------------------------

    public function test_finishing_checkout_turns_on_auto_renew_but_grants_no_time(): void
    {
        $this->webhook('checkout.session.completed', [
            'id' => 'cs_1', 'mode' => 'subscription', 'client_reference_id' => (string) $this->shop->id,
            'customer' => 'cus_1', 'subscription' => 'sub_1',
        ])->assertOk();

        $shop = $this->shop->refresh();
        $this->assertTrue($shop->hasAutoRenew());
        $this->assertSame('cus_1', $shop->stripe_customer_id);
        $this->assertNull($shop->paid_until); // only a paid invoice grants time
        $this->assertSame(1, SubscriptionEvent::where('type', 'subscribed')->count());
    }

    public function test_a_paid_invoice_extends_the_paid_period_once(): void
    {
        $this->link();
        $end = now()->addDays(60);

        $this->webhook('invoice.paid', $this->invoice(), id: 'evt_dup')->assertOk();
        $this->webhook('invoice.paid', $this->invoice(), id: 'evt_dup')->assertOk(); // Stripe delivers twice

        $shop = $this->shop->refresh();
        $this->assertSame(Shop::BILLING_PAID, $shop->billingStatus());
        $this->assertSame($end->timestamp, $shop->paid_until->timestamp);
        $this->assertSame(1, SubscriptionEvent::where('type', 'online_payment')->count());
    }

    public function test_a_free_trial_invoice_of_zero_grants_nothing(): void
    {
        $this->link();

        $this->webhook('invoice.paid', $this->invoice(['amount_paid' => 0]))->assertOk();

        $this->assertNull($this->shop->refresh()->paid_until);
    }

    public function test_an_expired_shop_comes_back_after_the_first_payment(): void
    {
        $this->shop->forceFill(['trial_ends_at' => now()->subDays(40)])->save();
        $this->assertTrue($this->shop->subscriptionExpired());
        $this->link();

        $this->webhook('invoice.paid', $this->invoice())->assertOk();

        $this->assertFalse($this->shop->refresh()->subscriptionExpired());
    }

    public function test_the_shop_is_found_by_metadata_when_the_customer_is_not_known_yet(): void
    {
        $this->webhook('invoice.paid', $this->invoice())->assertOk(); // no checkout event first

        $shop = $this->shop->refresh();
        $this->assertSame('cus_1', $shop->stripe_customer_id);
        $this->assertNotNull($shop->paid_until);
    }

    public function test_a_failed_payment_flags_the_shop_and_emails_the_owner_once(): void
    {
        Mail::fake();
        $this->link();
        User::factory()->for($this->shop)->create(['locale' => 'en']);

        $this->webhook('invoice.payment_failed', $this->invoice())->assertOk();
        $this->webhook('invoice.payment_failed', $this->invoice())->assertOk(); // Stripe's retry

        $this->assertNotNull($this->shop->refresh()->payment_failed_at);
        Mail::assertSent(SubscriptionReminder::class, 1);
        Mail::assertSent(SubscriptionReminder::class, fn (SubscriptionReminder $mail) => $mail->stage === 'payment_failed');

        $this->webhook('invoice.paid', $this->invoice())->assertOk();
        $this->assertNull($this->shop->refresh()->payment_failed_at);
    }

    public function test_cancelling_keeps_the_time_already_paid(): void
    {
        $this->link();
        $this->shop->recordOnlinePayment(now()->addDays(20));

        $this->webhook('customer.subscription.updated', ['id' => 'sub_1', 'customer' => 'cus_1', 'status' => 'active', 'cancel_at_period_end' => true])->assertOk();
        $this->assertFalse($this->shop->refresh()->hasAutoRenew());

        $this->webhook('customer.subscription.deleted', ['id' => 'sub_1', 'customer' => 'cus_1'])->assertOk();

        $shop = $this->shop->refresh();
        $this->assertNull($shop->stripe_subscription_id);
        $this->assertSame(Shop::BILLING_PAID, $shop->billingStatus());
        $this->assertSame(1, SubscriptionEvent::where('type', 'cancelled')->count());
    }

    public function test_an_event_for_an_unknown_shop_is_acknowledged_without_effect(): void
    {
        $this->webhook('invoice.paid', $this->invoice(['customer' => 'cus_nobody', 'subscription_details' => []]))->assertOk();

        $this->assertNull($this->shop->refresh()->paid_until);
    }

    public function test_unrelated_events_are_acknowledged(): void
    {
        $this->webhook('charge.refunded', ['id' => 'ch_1'])->assertOk();
    }

    // ---- Owner pages -------------------------------------------------------------------

    public function test_subscribing_sends_the_owner_to_stripe_checkout_with_the_remaining_trial(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['id' => 'cs_1', 'url' => 'https://checkout.stripe.com/pay/cs_1'])]);
        $owner = $this->signInOwner($this->shop);

        $this->post(route('billing.checkout'))->assertRedirect('https://checkout.stripe.com/pay/cs_1');

        Http::assertSent(function ($request) use ($owner) {
            $body = $request->data();

            return $request->url() === 'https://api.stripe.com/v1/checkout/sessions'
                && $request->hasHeader('Authorization', 'Bearer sk_test_123')
                && $body['mode'] === 'subscription'
                && $body['line_items'][0]['price'] === 'price_monthly'
                && $body['client_reference_id'] === (string) $this->shop->id
                && $body['customer_email'] === $owner->email
                && $body['subscription_data']['trial_end'] === $this->shop->trial_ends_at->timestamp
                && ! array_key_exists('amount', $body); // the price lives at Stripe, never in a browser request
        });
    }

    public function test_an_expired_owner_can_still_open_checkout_and_is_charged_immediately(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['url' => 'https://checkout.stripe.com/pay/cs_2'])]);
        $this->shop->forceFill(['trial_ends_at' => now()->subDays(30)])->save();
        $this->signInOwner($this->shop);

        $this->post(route('billing.checkout'))->assertRedirect('https://checkout.stripe.com/pay/cs_2');

        Http::assertSent(fn ($request) => ! isset($request->data()['subscription_data']['trial_end']));
    }

    public function test_checkout_is_skipped_when_auto_renew_is_already_on_and_stripe_errors_are_friendly(): void
    {
        Http::fake();
        $this->link();
        $this->signInOwner($this->shop);

        $this->post(route('billing.checkout'))->assertRedirect(route('billing.show'))->assertSessionHas('status', __('billing.pay.already'));
        Http::assertNothingSent();

        $this->shop->forceFill(['auto_renew' => false, 'stripe_subscription_id' => null])->save();
        Http::fake(['api.stripe.com/*' => Http::response(['error' => ['message' => 'nope']], 500)]);
        $owner = User::factory()->for($this->shop)->create();

        $this->actingAs($owner)->post(route('billing.checkout'))->assertRedirect(route('billing.show'))->assertSessionHas('status', __('billing.pay.failed'));
    }

    public function test_the_portal_needs_a_stripe_customer(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['url' => 'https://billing.stripe.com/session/x'])]);
        $this->signInOwner($this->shop);

        $this->post(route('billing.portal'))->assertNotFound();

        $this->link();
        $this->actingAs(User::factory()->for($this->shop->refresh())->create())->post(route('billing.portal'))->assertRedirect('https://billing.stripe.com/session/x');
    }

    public function test_payment_pages_are_off_when_stripe_is_not_configured(): void
    {
        config(['sofra.billing.stripe.secret' => null]);
        $this->signInOwner($this->shop);

        $this->post(route('billing.checkout'))->assertNotFound();
        $this->get(route('billing.show'))->assertOk()->assertDontSee(__('billing.pay.subscribe'));
    }

    public function test_the_billing_page_shows_the_right_button_and_a_success_url_grants_nothing(): void
    {
        $this->signInOwner($this->shop);

        $this->get(route('billing.show'))->assertOk()->assertSee(__('billing.pay.subscribe'))->assertSee(route('billing.checkout'), false);

        $this->get(route('billing.show', ['checkout' => 'success']))->assertOk()->assertSee(__('billing.pay.success'));
        $this->assertNull($this->shop->refresh()->paid_until); // the redirect proves nothing

        $this->link();
        $this->shop->forceFill(['paid_until' => now()->addDays(10)])->save();
        $this->actingAs(User::factory()->for($this->shop->refresh())->create())->get(route('billing.show'))
            ->assertOk()->assertSee(__('billing.pay.manage'))->assertDontSee(__('billing.pay.subscribe'));
    }

    public function test_owners_cannot_start_a_checkout_for_another_shop_and_guests_cannot_pay(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['url' => 'https://checkout.stripe.com/pay/cs_3'])]);
        $other = Shop::factory()->create();

        $this->post(route('billing.checkout'))->assertRedirect(route('login'));

        $this->signInOwner($other);
        $this->post(route('billing.checkout'));
        Http::assertSent(fn ($request) => $request->data()['client_reference_id'] === (string) $other->id); // always their own shop
    }

    public function test_auto_renewing_shops_get_no_please_renew_emails_before_the_due_date(): void
    {
        Mail::fake();
        User::factory()->for($this->shop)->create();
        $this->link();
        $this->shop->forceFill(['trial_ends_at' => now()->addDays(2)])->save();

        $this->artisan('sofra:subscription-reminders')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_stripe_is_a_reserved_word_so_no_shop_can_take_the_webhook_address(): void
    {
        $this->assertContains('stripe', config('sofra.reserved_slugs'));
    }
}
