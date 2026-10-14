<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSuggestion;
use App\Models\Shop;
use App\Support\ImageStore;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuickManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Shop $shop;

    private Product $coffee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::factory()->create([
            'slug' => 'kahve-evi', 'default_locale' => 'ar', 'enabled_locales' => ['ar', 'en'],
            'whatsapp_number' => '905551234567', 'timezone' => 'Europe/Istanbul',
        ]);
        $this->coffee = Product::factory()->for(Category::factory()->for($this->shop))->create([
            'shop_id' => $this->shop->id, 'name' => ['ar' => 'قهوة', 'en' => 'Coffee'], 'description' => ['ar' => 'طازجة'], 'price' => 85,
        ]);
    }

    /** 17:00-open weekdays etc. Monday-Friday 09:00–17:00 (Istanbul), plus Saturday 22:00–02:00. */
    private function schedule(): array
    {
        $week = [];
        foreach (range(0, 6) as $day) {
            $week[(string) $day] = ['open' => in_array($day, [1, 2, 3, 4, 5], true), 'from' => '09:00', 'to' => '17:00'];
        }
        $week['6'] = ['open' => true, 'from' => '22:00', 'to' => '02:00'];

        return $week;
    }

    private function istanbul(string $time): Carbon
    {
        return Carbon::parse($time, 'Europe/Istanbul');
    }

    private function order()
    {
        return $this->postJson('/kahve-evi/order', [
            'lang' => 'ar', 'type' => 'dine_in', 'table' => '2', 'items' => [['id' => $this->coffee->id, 'qty' => 1]],
        ]);
    }

    // ---- Ordering hours and pause ----------------------------------------

    public function test_without_a_schedule_or_pause_ordering_is_always_open(): void
    {
        $this->assertNull($this->shop->orderingClosedReason());
        $this->assertTrue($this->shop->acceptsOrders());
    }

    public function test_the_weekly_schedule_decides_when_ordering_is_open(): void
    {
        $this->shop->update(['order_hours' => $this->schedule()]);

        // 2026-10-12 is a Monday.
        $this->assertNull($this->shop->orderingClosedReason($this->istanbul('2026-10-12 09:00')));
        $this->assertNull($this->shop->orderingClosedReason($this->istanbul('2026-10-12 16:59')));
        $this->assertSame('hours', $this->shop->orderingClosedReason($this->istanbul('2026-10-12 17:00')));
        $this->assertSame('hours', $this->shop->orderingClosedReason($this->istanbul('2026-10-12 08:59')));
        $this->assertSame('hours', $this->shop->orderingClosedReason($this->istanbul('2026-10-11 12:00'))); // Sunday: closed
    }

    public function test_a_window_that_crosses_midnight_runs_into_the_next_day(): void
    {
        $this->shop->update(['order_hours' => $this->schedule()]);

        $this->assertNull($this->shop->orderingClosedReason($this->istanbul('2026-10-17 23:30')));  // Saturday night
        $this->assertNull($this->shop->orderingClosedReason($this->istanbul('2026-10-18 01:30')));  // after midnight, still open
        $this->assertSame('hours', $this->shop->orderingClosedReason($this->istanbul('2026-10-18 02:00')));
        $this->assertSame('hours', $this->shop->orderingClosedReason($this->istanbul('2026-10-17 21:59')));
    }

    public function test_the_shops_time_zone_is_used_not_the_servers(): void
    {
        $this->shop->update(['order_hours' => $this->schedule()]);

        // 07:00 UTC = 10:00 in Istanbul (UTC+3): open. 15:00 UTC = 18:00: closed.
        $this->assertNull($this->shop->orderingClosedReason(Carbon::parse('2026-10-12 07:00', 'UTC')));
        $this->assertSame('hours', $this->shop->orderingClosedReason(Carbon::parse('2026-10-12 15:00', 'UTC')));
    }

    public function test_the_next_opening_is_found(): void
    {
        $this->shop->update(['order_hours' => $this->schedule()]);

        $next = $this->shop->nextOrderingOpening($this->istanbul('2026-10-12 18:00')); // Monday evening
        $this->assertSame('2026-10-13 09:00', $next->format('Y-m-d H:i'));

        $this->assertNull(Shop::factory()->create()->nextOrderingOpening());
    }

    public function test_a_paused_shop_refuses_orders_with_a_clear_message_but_the_menu_stays_visible(): void
    {
        $this->shop->update(['orders_paused' => true]);

        $this->order()->assertStatus(422)->assertJsonPath('closed', 'paused')->assertJsonPath('message', __('quick.closed.paused'));
        $this->get('/kahve-evi')->assertOk()->assertSee($this->coffee->name)->assertSee(__('quick.closed.paused'))->assertDontSee('x-ref="cartBar"', false);

        $this->shop->update(['orders_paused' => false]);
        $this->order()->assertOk();
    }

    public function test_outside_ordering_hours_orders_are_refused(): void
    {
        $this->shop->update(['order_hours' => $this->schedule()]);

        $this->travelTo($this->istanbul('2026-10-12 12:00'));
        $this->order()->assertOk();

        $this->travelTo($this->istanbul('2026-10-12 20:00'));
        $this->order()->assertStatus(422)->assertJsonPath('closed', 'hours');
        $this->get('/kahve-evi')->assertOk()->assertSee(__('quick.closed.hours'));
    }

    // ---- Pause switch + hours page ------------------------------------------

    public function test_the_owner_can_pause_and_resume_orders_with_one_tap(): void
    {
        $owner = $this->signInOwner($this->shop);

        $this->patchJson(route('ordering.pause'), ['orders_paused' => true])->assertOk()->assertJsonPath('orders_paused', true);
        $this->assertTrue($owner->shop->refresh()->orders_paused);

        $this->patchJson(route('ordering.pause'), ['orders_paused' => false])->assertOk();
        $this->assertFalse($owner->shop->refresh()->orders_paused);

        $this->patchJson(route('ordering.pause'), ['orders_paused' => 'maybe'])->assertStatus(422);
    }

    public function test_pausing_one_shop_never_affects_another(): void
    {
        $other = Shop::factory()->create();
        $this->signInOwner($this->shop);

        $this->patchJson(route('ordering.pause'), ['orders_paused' => true])->assertOk();

        $this->assertFalse($other->refresh()->orders_paused);
    }

    public function test_the_hours_can_be_saved_and_switched_off(): void
    {
        $this->signInOwner($this->shop);
        $hours = collect(range(0, 6))->mapWithKeys(fn ($d) => [$d => ['open' => 1, 'from' => '10:00', 'to' => '22:30']])->all();

        $this->put(route('ordering.update'), ['hours_enabled' => 1, 'orders_paused' => 0, 'timezone' => 'Asia/Riyadh', 'hours' => $hours])
            ->assertRedirect(route('ordering.edit'))->assertSessionHasNoErrors();

        $saved = $this->shop->refresh();
        $this->assertSame('Asia/Riyadh', $saved->timezone);
        $this->assertSame(['open' => true, 'from' => '10:00', 'to' => '22:30'], $saved->order_hours['3']);
        $this->get(route('ordering.edit'))->assertOk();

        $this->put(route('ordering.update'), ['hours_enabled' => 0, 'timezone' => 'Asia/Riyadh'])->assertSessionHasNoErrors();
        $this->assertNull($this->shop->refresh()->order_hours);
    }

    public function test_hours_are_validated(): void
    {
        $this->signInOwner($this->shop);
        $all = fn (array $row) => collect(range(0, 6))->mapWithKeys(fn ($d) => [$d => $row])->all();

        $this->put(route('ordering.update'), ['hours_enabled' => 1, 'timezone' => 'Europe/Istanbul', 'hours' => $all(['open' => 0, 'from' => '09:00', 'to' => '17:00'])])
            ->assertSessionHasErrors('hours');
        $this->put(route('ordering.update'), ['hours_enabled' => 1, 'timezone' => 'Europe/Istanbul', 'hours' => $all(['open' => 1, 'from' => '09:00', 'to' => '09:00'])])
            ->assertSessionHasErrors('hours.0.to');
        $this->put(route('ordering.update'), ['hours_enabled' => 1, 'timezone' => 'Europe/Istanbul', 'hours' => $all(['open' => 1, 'from' => '9am', 'to' => '25:00'])])
            ->assertSessionHasErrors(['hours.0.from', 'hours.0.to']);
        $this->put(route('ordering.update'), ['hours_enabled' => 0, 'timezone' => 'Mars/Olympus'])->assertSessionHasErrors('timezone');

        $this->assertNull($this->shop->refresh()->order_hours);
    }

    // ---- Quick product edits ----------------------------------------------------

    public function test_the_today_page_lists_only_the_owners_products(): void
    {
        $other = Shop::factory()->create();
        Product::factory()->for(Category::factory()->for($other))->create(['shop_id' => $other->id, 'name' => ['ar' => 'منتج غريب', 'en' => 'Foreign']]);
        $this->signInOwner($this->shop);

        $this->get(route('today'))->assertOk()->assertSee('قهوة')->assertDontSee('منتج غريب');
    }

    private function quick(Product $product, array $fields)
    {
        return $this->patchJson(route('products.quick', $product), $fields);
    }

    public function test_price_and_text_can_be_changed_without_touching_anything_else(): void
    {
        $this->signInOwner($this->shop);

        $this->quick($this->coffee, ['price' => '95,50'])->assertOk()->assertJsonPath('product.price', 95.5);

        $fresh = $this->coffee->refresh();
        $this->assertSame('95.50', $fresh->price);
        $this->assertSame(['ar' => 'قهوة', 'en' => 'Coffee'], $fresh->getTranslations('name'));
        $this->assertSame('طازجة', $fresh->getTranslation('description', 'ar'));

        $this->quick($this->coffee, ['name' => ['ar' => 'قهوة تركية', 'en' => 'Turkish coffee'], 'description' => ['ar' => 'بالهيل']])->assertOk();
        $this->assertSame('Turkish coffee', $this->coffee->refresh()->getTranslation('name', 'en'));
        $this->assertSame('95.50', $this->coffee->price);
    }

    public function test_quick_edits_are_validated_on_the_server(): void
    {
        $this->signInOwner($this->shop);

        $this->quick($this->coffee, ['price' => 'abc'])->assertStatus(422)->assertJsonValidationErrors('price');
        $this->quick($this->coffee, ['price' => '-5'])->assertStatus(422);
        $this->quick($this->coffee, ['price' => ''])->assertStatus(422);
        $this->quick($this->coffee, ['name' => ['en' => 'Only English']])->assertStatus(422)->assertJsonValidationErrors('name.ar'); // main language missing
        $this->quick($this->coffee, ['name' => ['ar' => str_repeat('x', 81)]])->assertStatus(422);
        $this->quick($this->coffee, ['compare_at_price' => '50'])->assertStatus(422)->assertJsonValidationErrors('compare_at_price'); // not above 85

        $this->assertSame('85.00', $this->coffee->refresh()->price);
    }

    public function test_a_sale_can_be_started_and_ended(): void
    {
        $this->signInOwner($this->shop);

        $this->quick($this->coffee, ['compare_at_price' => '120'])->assertOk()->assertJsonPath('product.on_sale', true);
        $this->assertTrue($this->coffee->refresh()->isOnSale());

        $this->quick($this->coffee, ['compare_at_price' => ''])->assertOk()->assertJsonPath('product.on_sale', false);
        $this->assertNull($this->coffee->refresh()->compare_at_price);
        $this->assertSame('85.00', $this->coffee->price);
    }

    public function test_the_photo_can_be_replaced_and_removed(): void
    {
        Storage::fake('public');
        $this->signInOwner($this->shop);

        $this->post(route('products.quick', $this->coffee), ['_method' => 'PATCH', 'image' => UploadedFile::fake()->image('a.jpg', 900, 900)], ['Accept' => 'application/json'])->assertOk();
        $path = $this->coffee->refresh()->image_path;
        Storage::disk('public')->assertExists($path);

        $this->post(route('products.quick', $this->coffee), ['_method' => 'PATCH', 'remove_image' => 1], ['Accept' => 'application/json'])->assertOk();
        $this->assertNull($this->coffee->refresh()->image_path);
        Storage::disk('public')->assertMissing([$path, ImageStore::variantPath($path, 'sm')]);

        $this->post(route('products.quick', $this->coffee), ['_method' => 'PATCH', 'image' => UploadedFile::fake()->create('evil.php.jpg', 10, 'application/x-php')], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('image');
    }

    public function test_an_owner_cannot_edit_another_shops_product_or_offer(): void
    {
        $other = Shop::factory()->create();
        $foreign = Product::factory()->for(Category::factory()->for($other))->create(['shop_id' => $other->id, 'price' => 10]);
        $offer = ProductSuggestion::factory()->create(['shop_id' => $other->id, 'product_id' => $foreign->id, 'suggested_product_id' => Product::factory()->for(Category::factory()->for($other))->create(['shop_id' => $other->id])->id]);
        $this->signInOwner($this->shop);

        $this->quick($foreign, ['price' => '1'])->assertNotFound();
        $this->patchJson(route('offers.active', $offer), ['is_active' => false])->assertNotFound();

        $this->assertSame('10.00', $foreign->refresh()->price);
        $this->assertTrue($offer->refresh()->is_active);
    }

    public function test_guests_cannot_use_any_quick_action(): void
    {
        $this->getJson(route('today'))->assertUnauthorized();
        $this->patchJson(route('products.quick', $this->coffee), ['price' => '1'])->assertUnauthorized();
        $this->patchJson(route('ordering.pause'), ['orders_paused' => true])->assertUnauthorized();

        $this->assertSame('85.00', $this->coffee->refresh()->price);
    }

    public function test_offers_can_be_switched_on_and_off(): void
    {
        $side = Product::factory()->for($this->coffee->category)->create(['shop_id' => $this->shop->id]);
        $offer = ProductSuggestion::factory()->percent(10)->create(['shop_id' => $this->shop->id, 'product_id' => $this->coffee->id, 'suggested_product_id' => $side->id]);
        $this->signInOwner($this->shop);

        $this->get(route('today'))->assertOk()->assertSee(__('quick.promos.title'));

        $this->patchJson(route('offers.active', $offer), ['is_active' => false])->assertOk()->assertJsonPath('is_active', false);
        $this->assertFalse($offer->refresh()->is_active);

        $this->patchJson(route('offers.active', $offer), ['is_active' => true])->assertOk();
        $this->assertTrue($offer->refresh()->is_active);
    }

    public function test_changes_show_on_the_public_menu_right_away(): void
    {
        $this->signInOwner($this->shop);

        $this->quick($this->coffee, ['price' => '99'])->assertOk();
        $this->patchJson(route('products.availability', $this->coffee), ['is_available' => false])->assertOk();

        $this->get('/kahve-evi')->assertOk()->assertSee('99 ₺');
        $this->postJson('/kahve-evi/order', [
            'lang' => 'ar', 'type' => 'dine_in', 'table' => '2', 'items' => [['id' => $this->coffee->id, 'qty' => 1]],
        ])->assertStatus(422)->assertJsonPath('unavailable.0', $this->coffee->id);
    }
}
