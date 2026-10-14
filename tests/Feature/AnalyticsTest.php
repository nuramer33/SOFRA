<?php

namespace Tests\Feature;

use App\Models\AnalyticsDaily;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Support\Analytics;
use App\Tenancy\CurrentShop;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Shop $shop;

    private Category $category;

    private Product $coffee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::factory()->create(['slug' => 'kahve-evi', 'whatsapp_number' => '905551234567']);
        $this->category = Category::factory()->for($this->shop)->create();
        $this->coffee = Product::factory()->for($this->category)->create(['shop_id' => $this->shop->id, 'price' => 85]);
    }

    /** Total counted for an event, bypassing the tenant scope. */
    private function counted(string $event, ?Shop $shop = null, ?int $productId = null): int
    {
        return (int) AnalyticsDaily::withoutGlobalScopes()
            ->where('shop_id', ($shop ?? $this->shop)->id)
            ->where('event', $event)
            ->when($productId !== null, fn ($query) => $query->where('product_id', $productId))
            ->sum('total');
    }

    /** A request with a fixed visitor session, so de-duplication can be tested. */
    private function visitor(string $sessionId = 'visitor-one', array $headers = []): Request
    {
        $request = Request::create('/kahve-evi', 'GET', server: array_merge(['HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone)'], $headers));
        $session = app('session.store');
        $session->setId(str_pad($sessionId, 40, 'x'));
        $request->setLaravelSession($session);

        return $request;
    }

    public function test_opening_the_menu_counts_a_menu_view(): void
    {
        $this->get('/kahve-evi')->assertOk();

        $this->assertSame(1, $this->counted(Analytics::MENU_VIEW));
    }

    public function test_the_same_visitor_is_counted_once_but_another_visitor_is_counted_too(): void
    {
        $this->assertTrue(Analytics::record($this->shop, $this->visitor('one'), Analytics::MENU_VIEW));
        $this->assertFalse(Analytics::record($this->shop, $this->visitor('one'), Analytics::MENU_VIEW));
        $this->assertTrue(Analytics::record($this->shop, $this->visitor('two'), Analytics::MENU_VIEW));

        $this->assertSame(2, $this->counted(Analytics::MENU_VIEW));
    }

    public function test_one_visitor_cannot_write_more_than_the_hourly_cap(): void
    {
        $counted = 0;
        foreach (range(1, 400) as $product) {
            $counted += (int) Analytics::record($this->shop, $this->visitor('spammer'), Analytics::PRODUCT_VIEW, $product);
        }

        $this->assertSame(300, $counted);
    }

    public function test_do_not_track_bots_and_the_owner_are_not_counted(): void
    {
        $this->assertFalse(Analytics::record($this->shop, $this->visitor('a', ['HTTP_DNT' => '1']), Analytics::MENU_VIEW));
        $this->assertFalse(Analytics::record($this->shop, $this->visitor('b', ['HTTP_USER_AGENT' => 'WhatsApp/2.23']), Analytics::MENU_VIEW));
        $this->assertFalse(Analytics::record($this->shop, $this->visitor('c', ['HTTP_USER_AGENT' => 'Googlebot/2.1']), Analytics::MENU_VIEW));

        $this->signInOwner($this->shop);
        $this->get('/kahve-evi')->assertOk();

        $this->assertSame(0, $this->counted(Analytics::MENU_VIEW));
    }

    public function test_product_views_and_cart_additions_are_counted_per_product(): void
    {
        $this->postJson('/kahve-evi/track', ['event' => 'product_view', 'product' => $this->coffee->id])->assertNoContent();
        $this->postJson('/kahve-evi/track', ['event' => 'add_to_cart', 'product' => $this->coffee->id])->assertNoContent();

        $this->assertSame(1, $this->counted(Analytics::PRODUCT_VIEW, productId: $this->coffee->id));
        $this->assertSame(1, $this->counted(Analytics::ADD_TO_CART, productId: $this->coffee->id));
        $this->assertSame(0, $this->counted(Analytics::WHATSAPP_CLICK));
    }

    public function test_unavailable_hidden_or_foreign_products_are_not_counted(): void
    {
        $soldOut = Product::factory()->for($this->category)->create(['shop_id' => $this->shop->id, 'is_available' => false]);
        $hidden = Product::factory()->for(Category::factory()->for($this->shop)->create(['is_active' => false]))->create(['shop_id' => $this->shop->id]);
        $other = Shop::factory()->create();
        $foreign = Product::factory()->for(Category::factory()->for($other))->create(['shop_id' => $other->id]);

        $this->postJson('/kahve-evi/track', ['event' => 'add_to_cart', 'product' => $soldOut->id])->assertNoContent();
        $this->postJson('/kahve-evi/track', ['event' => 'product_view', 'product' => $hidden->id])->assertNoContent();
        $this->postJson('/kahve-evi/track', ['event' => 'product_view', 'product' => $foreign->id])->assertNoContent();

        $this->assertSame(0, AnalyticsDaily::withoutGlobalScopes()->count());
    }

    public function test_the_tracking_endpoint_rejects_unknown_events_and_inactive_shops(): void
    {
        $this->postJson('/kahve-evi/track', ['event' => 'order_completed', 'product' => $this->coffee->id])->assertStatus(422);
        $this->postJson('/kahve-evi/track', ['event' => 'menu_view', 'product' => $this->coffee->id])->assertStatus(422);

        $this->shop->update(['is_active' => false]);
        $this->postJson('/kahve-evi/track', ['event' => 'product_view', 'product' => $this->coffee->id])->assertNotFound();

        $this->assertSame(0, AnalyticsDaily::withoutGlobalScopes()->count());
    }

    public function test_pressing_the_whatsapp_button_counts_a_click_not_an_order(): void
    {
        $this->postJson('/kahve-evi/order', [
            'lang' => 'en', 'items' => [['id' => $this->coffee->id, 'qty' => 1]], 'type' => 'dine_in', 'table' => '5',
        ])->assertOk();

        $this->assertSame(1, $this->counted(Analytics::WHATSAPP_CLICK));
        $this->assertSame([], array_diff(Schema::getColumnListing('analytics_dailies'), ['id', 'shop_id', 'day', 'event', 'product_id', 'total']));
    }

    public function test_a_rejected_order_is_not_counted_as_a_click(): void
    {
        $this->coffee->update(['is_available' => false]);

        $this->postJson('/kahve-evi/order', [
            'lang' => 'en', 'items' => [['id' => $this->coffee->id, 'qty' => 1]], 'type' => 'pickup',
        ])->assertStatus(422);

        $this->assertSame(0, $this->counted(Analytics::WHATSAPP_CLICK));
    }

    public function test_the_dashboard_needs_a_login(): void
    {
        $this->get(route('analytics.index'))->assertRedirect(route('login'));
    }

    public function test_the_dashboard_shows_only_the_owners_own_numbers(): void
    {
        $other = Shop::factory()->create();
        AnalyticsDaily::factory()->create(['shop_id' => $this->shop->id, 'event' => Analytics::MENU_VIEW, 'total' => 7]);
        AnalyticsDaily::factory()->create(['shop_id' => $other->id, 'event' => Analytics::MENU_VIEW, 'total' => 4321]);
        AnalyticsDaily::factory()->create(['shop_id' => $this->shop->id, 'event' => Analytics::WHATSAPP_CLICK, 'total' => 3]);

        $this->signInOwner($this->shop);

        $this->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('>7<', false)
            ->assertDontSee('4321')
            ->assertSee(__('analytics.kpi.whatsapp_click'))
            ->assertSee(__('analytics.honesty_text'))
            ->assertDontSee('4,321');
    }

    public function test_the_dashboard_lists_popular_products_and_supports_each_grouping(): void
    {
        AnalyticsDaily::factory()->create([
            'shop_id' => $this->shop->id, 'event' => Analytics::PRODUCT_VIEW, 'product_id' => $this->coffee->id, 'total' => 9,
        ]);
        AnalyticsDaily::factory()->create([
            'shop_id' => $this->shop->id, 'day' => today()->subDays(40)->toDateString(), 'event' => Analytics::MENU_VIEW, 'total' => 5,
        ]);

        $this->signInOwner($this->shop);

        $this->get(route('analytics.index', ['range' => 7]))->assertOk()->assertSee($this->coffee->name);
        $this->get(route('analytics.index', ['range' => 90, 'group' => 'week']))->assertOk();
        $this->get(route('analytics.index', ['range' => 90, 'group' => 'month']))->assertOk();
        $this->get(route('analytics.index', ['range' => 'nonsense', 'group' => 'nonsense']))->assertOk();
    }

    public function test_another_shops_product_never_appears_in_the_popular_list(): void
    {
        $other = Shop::factory()->create();
        $foreign = Product::factory()->for(Category::factory()->for($other))->create([
            'shop_id' => $other->id, 'name' => ['ar' => 'منتج غريب', 'tr' => 'Yabanci', 'en' => 'Foreign item'],
        ]);
        AnalyticsDaily::factory()->create([
            'shop_id' => $this->shop->id, 'event' => Analytics::PRODUCT_VIEW, 'product_id' => $foreign->id, 'total' => 99,
        ]);

        $this->signInOwner($this->shop);

        $this->get(route('analytics.index'))->assertOk()->assertDontSee('Foreign item');
    }

    public function test_the_owner_can_pick_a_retention_period_from_the_allowed_list(): void
    {
        $owner = $this->signInOwner($this->shop);
        $payload = [
            'name' => ['ar' => 'بيت القهوة'], 'slug' => 'kahve-evi', 'brand_color' => '#1f6f6b', 'currency' => 'TRY',
            'enabled_locales' => ['ar'], 'default_locale' => 'ar', 'order_types' => ['pickup'],
        ];

        $this->put(route('settings.update'), [...$payload, 'analytics_retention_days' => 30])->assertSessionHasNoErrors();
        $this->assertSame(30, $owner->shop->refresh()->analytics_retention_days);

        $this->put(route('settings.update'), [...$payload, 'analytics_retention_days' => 3650])->assertSessionHasErrors('analytics_retention_days');
        $this->assertSame(30, $owner->shop->refresh()->analytics_retention_days);
    }

    public function test_old_counters_are_pruned_per_shops_retention_period(): void
    {
        $short = Shop::factory()->create(['analytics_retention_days' => 30]);
        $long = Shop::factory()->create(['analytics_retention_days' => 365]);
        $old = today()->subDays(60)->toDateString();

        foreach ([$short, $long] as $shop) {
            AnalyticsDaily::factory()->create(['shop_id' => $shop->id, 'day' => $old]);
            AnalyticsDaily::factory()->create(['shop_id' => $shop->id, 'day' => today()->toDateString()]);
        }

        $this->artisan('sofra:prune-analytics')->assertSuccessful();

        $this->assertSame(1, AnalyticsDaily::withoutGlobalScopes()->where('shop_id', $short->id)->count());
        $this->assertSame(2, AnalyticsDaily::withoutGlobalScopes()->where('shop_id', $long->id)->count());
    }

    public function test_deleting_a_product_removes_its_counters(): void
    {
        AnalyticsDaily::factory()->create(['shop_id' => $this->shop->id, 'event' => Analytics::PRODUCT_VIEW, 'product_id' => $this->coffee->id]);
        AnalyticsDaily::factory()->create(['shop_id' => $this->shop->id, 'event' => Analytics::MENU_VIEW]);

        app(CurrentShop::class)->set($this->shop);
        $this->coffee->delete();

        $this->assertSame(1, AnalyticsDaily::withoutGlobalScopes()->count());
    }
}
