<?php

namespace Tests\Feature\Models;

use App\Models\Shop;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ShopBillingTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeSecond(); // the database stores whole seconds
    }

    public function test_new_shop_is_in_trial_for_sixty_days(): void
    {
        $shop = Shop::factory()->create(['trial_ends_at' => null]);
        $shop->startTrial();

        $this->assertTrue($shop->trial_ends_at->equalTo(now()->addDays(60)));
        $this->assertSame(Shop::BILLING_TRIAL, $shop->billingStatus());
    }

    public function test_last_week_of_trial_is_flagged_as_ending(): void
    {
        $shop = Shop::factory()->create(['trial_ends_at' => now()->addDays(5)]);

        $this->assertSame(Shop::BILLING_TRIAL_ENDING, $shop->billingStatus());
        $this->assertSame(5, $shop->trialDaysLeft());
    }

    public function test_payment_becomes_past_due_then_expired_after_the_grace_period(): void
    {
        $shop = Shop::factory()->create(['trial_ends_at' => now()->addDays(60)]);

        $this->travel(61)->days();
        $this->assertSame(Shop::BILLING_PAST_DUE, $shop->billingStatus());
        $this->assertSame(0, $shop->trialDaysLeft());
        $this->assertFalse($shop->subscriptionExpired());

        $this->travel(7)->days(); // 68 days: grace (7) is over
        $this->assertSame(Shop::BILLING_EXPIRED, $shop->billingStatus());
        $this->assertTrue($shop->subscriptionExpired());
    }

    public function test_payment_during_trial_starts_after_the_trial_so_no_free_days_are_lost(): void
    {
        $trialEnd = now()->addDays(20);
        $shop = Shop::factory()->create(['trial_ends_at' => $trialEnd]);

        $shop->recordPayment(1);

        $this->assertTrue($shop->paid_until->equalTo($trialEnd->copy()->addMonth()));
        $this->assertSame(Shop::BILLING_PAID, $shop->billingStatus());
    }

    public function test_late_payment_counts_from_today(): void
    {
        $shop = Shop::factory()->create(['trial_ends_at' => now()->subMonth()]);

        $shop->recordPayment(3);

        $this->assertTrue($shop->paid_until->equalTo(now()->addMonths(3)));
    }

    public function test_billing_scope_matches_the_status(): void
    {
        $trial = Shop::factory()->create(['trial_ends_at' => now()->addMonth()]);
        $due = Shop::factory()->create(['trial_ends_at' => now()->subDay()]);
        $expired = Shop::factory()->create(['trial_ends_at' => now()->subDays(30), 'paid_until' => now()->subDays(20)]);
        $neverSet = Shop::factory()->create(['trial_ends_at' => null]);
        $paid = Shop::factory()->create(['trial_ends_at' => now()->subMonth(), 'paid_until' => now()->addMonth()]);

        $this->assertSame([$trial->id], Shop::billing(Shop::BILLING_TRIAL)->pluck('id')->all());
        $this->assertSame([$due->id], Shop::billing(Shop::BILLING_PAST_DUE)->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$expired->id, $neverSet->id], Shop::billing(Shop::BILLING_EXPIRED)->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$due->id, $expired->id, $neverSet->id], Shop::billing(Shop::BILLING_DUE)->pluck('id')->all());
        $this->assertSame(Shop::BILLING_PAST_DUE, $due->billingStatus());
        $this->assertSame(Shop::BILLING_EXPIRED, $expired->billingStatus());
        $this->assertSame(Shop::BILLING_EXPIRED, $neverSet->billingStatus());
        $this->assertSame([$paid->id], Shop::billing(Shop::BILLING_PAID)->pluck('id')->all());
    }
}
