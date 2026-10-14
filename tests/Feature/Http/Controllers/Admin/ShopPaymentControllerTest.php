<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ShopPaymentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_records_a_payment(): void
    {
        $this->freezeSecond(); // the database stores whole seconds
        $this->actingAs(User::factory()->admin()->create());
        $shop = Shop::factory()->create(['trial_ends_at' => now()->subDay()]);

        $this->post(route('admin.shops.payments', $shop), ['months' => 3])->assertRedirect();

        $this->assertTrue($shop->refresh()->paid_until->equalTo(now()->addMonths(3)));
    }

    public function test_only_allowed_month_counts_are_accepted(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $shop = Shop::factory()->create();

        $this->post(route('admin.shops.payments', $shop), ['months' => 240])->assertSessionHasErrors('months');
        $this->assertNull($shop->refresh()->paid_until);
    }

    public function test_shop_owner_cannot_mark_their_own_shop_as_paid(): void
    {
        $owner = $this->signInOwner();

        $this->post(route('admin.shops.payments', $owner->shop), ['months' => 12])->assertNotFound();
        $this->assertNull($owner->shop->refresh()->paid_until);
    }

    public function test_due_filter_lists_shops_whose_trial_ended(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Shop::factory()->create(['slug' => 'still-free', 'trial_ends_at' => now()->addMonth()]);
        Shop::factory()->create(['slug' => 'must-pay', 'trial_ends_at' => now()->subDay()]);

        $this->get(route('admin.index', ['status' => 'due']))->assertSee('/must-pay')->assertDontSee('/still-free');
    }
}
