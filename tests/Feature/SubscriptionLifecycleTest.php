<?php

namespace Tests\Feature;

use App\Mail\SubscriptionReminder;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\SubscriptionEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SubscriptionLifecycleTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Shop $shop;

    private Product $coffee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::factory()->create(['slug' => 'kahve-evi', 'whatsapp_number' => '905551234567', 'trial_ends_at' => now()->addDays(30)]);
        $this->coffee = Product::factory()->for(Category::factory()->for($this->shop))->create(['shop_id' => $this->shop->id, 'price' => 85]);
    }

    /** Puts the shop 30 days past its due date, beyond the 7-day grace period. */
    private function expire(?Shop $shop = null): Shop
    {
        ($shop ?? $this->shop)->forceFill(['trial_ends_at' => now()->subDays(30), 'paid_until' => null])->save();

        return $shop ?? $this->shop;
    }

    private function order()
    {
        return $this->postJson('/kahve-evi/order', [
            'lang' => 'en', 'type' => 'dine_in', 'table' => '3', 'items' => [['id' => $this->coffee->id, 'qty' => 1]],
        ]);
    }

    private function admin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        return $admin;
    }

    // ---- Public menu policy (server-side) ---------------------------------

    public function test_a_healthy_shop_takes_orders(): void
    {
        $this->get('/kahve-evi')->assertOk();
        $this->order()->assertOk();
    }

    public function test_a_past_due_shop_inside_the_grace_period_still_works_fully(): void
    {
        $this->shop->forceFill(['trial_ends_at' => now()->subDays(3)])->save();

        $this->assertSame(Shop::BILLING_PAST_DUE, $this->shop->billingStatus());
        $this->get('/kahve-evi')->assertOk();
        $this->order()->assertOk();
    }

    public function test_by_default_an_expired_menu_stays_visible_but_orders_are_refused(): void
    {
        $this->expire();

        $this->get('/kahve-evi')->assertOk()->assertSee($this->coffee->name);
        $this->order()->assertNotFound();
    }

    public function test_the_visible_policy_keeps_everything_working_after_expiry(): void
    {
        config(['sofra.billing.expired_menu' => 'visible']);
        $this->expire();

        $this->get('/kahve-evi')->assertOk();
        $this->order()->assertOk();
    }

    public function test_the_suspended_policy_hides_an_expired_menu_everywhere(): void
    {
        config(['sofra.billing.expired_menu' => 'suspended']);
        $this->expire();

        $this->get('/kahve-evi')->assertNotFound();
        $this->get('/kahve-evi/share.jpg')->assertNotFound();
        $this->order()->assertNotFound();
        $this->postJson('/kahve-evi/track', ['event' => 'product_view', 'product' => $this->coffee->id])->assertNotFound();
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('/kahve-evi');
    }

    public function test_renewing_restores_the_menu_immediately(): void
    {
        $this->expire();
        $this->order()->assertNotFound();

        $this->shop->recordPayment(1);

        $this->order()->assertOk();
    }

    public function test_the_public_demo_shop_never_expires(): void
    {
        $demo = Shop::factory()->create(['slug' => config('sofra.demo_slug'), 'trial_ends_at' => now()->subYear()]);

        $this->assertFalse($demo->subscriptionExpired());
        $this->assertTrue($demo->acceptsOrders());
    }

    // ---- Owner dashboard ---------------------------------------------------

    public function test_an_expired_owner_can_read_but_not_change_anything(): void
    {
        $owner = $this->signInOwner($this->expire());

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('products.index'))->assertOk();
        $this->get(route('billing.show'))->assertOk();

        $this->patch(route('products.availability', $this->coffee), ['is_available' => false])
            ->assertRedirect(route('billing.show'));
        $this->assertTrue($this->coffee->refresh()->is_available);

        $this->patchJson(route('products.availability', $this->coffee), ['is_available' => false])->assertForbidden();
        $this->put(route('settings.update'), ['slug' => 'hacked'])->assertRedirect(route('billing.show'));
        $this->assertSame('kahve-evi', $owner->shop->refresh()->slug);

        // The account page stays usable so the owner is never locked out of their own login.
        $this->put(route('account.update'), ['name' => 'New Name', 'email' => $owner->email])->assertSessionHasNoErrors();
    }

    public function test_an_active_owner_can_still_make_changes(): void
    {
        $this->signInOwner($this->shop);

        $this->patchJson(route('products.availability', $this->coffee), ['is_available' => false])->assertOk();
        $this->assertFalse($this->coffee->refresh()->is_available);
    }

    public function test_the_billing_page_and_notices_show_the_right_state(): void
    {
        $owner = $this->signInOwner($this->shop);
        $this->get(route('billing.show'))->assertOk()->assertSee(__('billing.states.trial'));

        // A fresh login per step: the signed-in user object caches its shop.
        $this->shop->forceFill(['trial_ends_at' => now()->addDays(3)])->save();
        $this->actingAs($owner->fresh())->get(route('products.index'))->assertSee(__('billing.notice.cta'));

        $this->shop->forceFill(['trial_ends_at' => now()->subDays(2)])->save();
        $this->actingAs($owner->fresh())->get(route('products.index'))->assertSee(__('billing.notice.past_due_title'));

        $this->expire();
        $this->actingAs($owner->fresh())->get(route('products.index'))->assertSee(__('billing.notice.expired_title'));
        $this->get(route('billing.show'))->assertSee(__('billing.states.expired'));
    }

    public function test_one_owner_never_sees_another_shops_subscription(): void
    {
        $other = Shop::factory()->create(['trial_ends_at' => now()->subYear(), 'slug' => 'someone-else']);
        $this->signInOwner($this->shop);

        $this->get(route('billing.show'))->assertOk()->assertDontSee('someone-else');
        $this->assertTrue($other->subscriptionExpired());
        $this->assertFalse($this->shop->subscriptionExpired());
    }

    // ---- Administrator -----------------------------------------------------

    public function test_an_admin_can_extend_the_trial_and_the_paid_period(): void
    {
        $admin = $this->admin();
        $this->freezeSecond();

        $trialEnd = $this->shop->trial_ends_at;
        $this->post(route('admin.shops.extensions', $this->shop), ['days' => 14])->assertSessionHasNoErrors();
        $this->assertTrue($this->shop->refresh()->trial_ends_at->equalTo($trialEnd->copy()->addDays(14)));
        $this->assertNull($this->shop->paid_until);

        $this->shop->recordPayment(1);
        $paidEnd = $this->shop->refresh()->paid_until;
        $this->post(route('admin.shops.extensions', $this->shop), ['days' => 7]);
        $this->assertTrue($this->shop->refresh()->paid_until->equalTo($paidEnd->copy()->addDays(7)));

        $this->assertSame(2, SubscriptionEvent::where('shop_id', $this->shop->id)->where('type', 'extension')->where('user_id', $admin->id)->count());
    }

    public function test_extending_an_expired_shop_restores_access_without_a_payment(): void
    {
        $this->admin();
        $this->expire();
        $this->order()->assertNotFound();

        $this->post(route('admin.shops.extensions', $this->shop), ['days' => 30])->assertSessionHasNoErrors();

        $this->assertContains($this->shop->refresh()->billingStatus(), [Shop::BILLING_TRIAL, Shop::BILLING_TRIAL_ENDING]);
        $this->assertFalse($this->shop->subscriptionExpired());
        $this->order()->assertOk();
    }

    public function test_extension_days_are_validated_and_owners_cannot_extend(): void
    {
        $this->admin();
        $this->post(route('admin.shops.extensions', $this->shop), ['days' => 9999])->assertSessionHasErrors('days');

        $this->signInOwner($this->shop);
        $this->post(route('admin.shops.extensions', $this->shop), ['days' => 7])->assertNotFound();
    }

    public function test_admin_actions_are_logged_and_a_manual_suspension_is_separate_from_billing(): void
    {
        $admin = $this->admin();

        $this->post(route('admin.shops.payments', $this->shop), ['months' => 3])->assertSessionHasNoErrors();
        $this->patch(route('admin.shops.status', $this->shop))->assertSessionHasNoErrors();

        $this->assertFalse($this->shop->refresh()->is_active);
        $this->assertSame(Shop::BILLING_PAID, $this->shop->billingStatus()); // paid, yet suspended
        $this->get('/kahve-evi')->assertNotFound();

        $this->patch(route('admin.shops.status', $this->shop));
        $this->get('/kahve-evi')->assertOk();

        $this->assertEqualsCanonicalizing(
            ['payment', 'suspension', 'activation'],
            SubscriptionEvent::where('user_id', $admin->id)->pluck('type')->all(),
        );
        $this->get(route('admin.shops.show', $this->shop))->assertOk()->assertSee(__('billing.admin.extend'));
    }

    public function test_the_admin_can_filter_expired_shops(): void
    {
        $this->admin();
        $expired = $this->expire(Shop::factory()->create(['slug' => 'gone-quiet']));

        $this->get(route('admin.index', ['status' => 'expired']))->assertOk()->assertSee('/'.$expired->slug)->assertDontSee('/kahve-evi');
    }

    // ---- Reminders -----------------------------------------------------------

    private function owner(): User
    {
        return User::factory()->for($this->shop)->create(['locale' => 'en']);
    }

    public function test_reminders_go_out_once_per_stage_and_again_at_the_next_stage(): void
    {
        Mail::fake();
        $this->owner();

        $this->shop->forceFill(['trial_ends_at' => now()->addDays(20)])->save();
        $this->artisan('sofra:subscription-reminders')->assertSuccessful();
        Mail::assertNothingSent();

        $this->shop->forceFill(['trial_ends_at' => now()->addDays(6)])->save(); // inside the 7-day window
        $this->artisan('sofra:subscription-reminders');
        $this->artisan('sofra:subscription-reminders'); // a second run sends nothing new
        Mail::assertSent(SubscriptionReminder::class, 1);

        $this->shop->forceFill(['trial_ends_at' => now()->addDays(2)])->save(); // now the 3-day window
        $this->artisan('sofra:subscription-reminders');
        Mail::assertSent(SubscriptionReminder::class, 2);
    }

    public function test_reminders_cover_past_due_and_expired_but_not_suspended_shops(): void
    {
        Mail::fake();
        $this->owner();

        $this->shop->forceFill(['trial_ends_at' => now()->subDays(2)])->save();
        $this->artisan('sofra:subscription-reminders');
        Mail::assertSent(SubscriptionReminder::class, fn (SubscriptionReminder $mail) => $mail->stage === 'past_due');

        $this->expire();
        $this->artisan('sofra:subscription-reminders');
        Mail::assertSent(SubscriptionReminder::class, fn (SubscriptionReminder $mail) => $mail->stage === 'expired');

        Mail::fake();
        $this->shop->forceFill(['is_active' => false, 'last_billing_notice' => null])->save();
        $this->artisan('sofra:subscription-reminders');
        Mail::assertNothingSent();
    }

    public function test_the_reminder_email_renders_in_the_owners_language(): void
    {
        $owner = $this->owner();
        $this->shop->forceFill(['trial_ends_at' => now()->addDays(3)])->save();

        $mail = new SubscriptionReminder($this->shop, $owner, '3', 'tr');

        $mail->assertHasSubject(__('billing.mail.subject_ending', ['shop' => $this->shop->name, 'days' => 3], 'tr'));
        $mail->assertSeeInHtml(__('billing.mail.cta', [], 'tr'));
    }
}
