<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ShopInfoTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Shop $shop;

    private Product $coffee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::factory()->create([
            'slug' => 'kahve-evi', 'default_locale' => 'en', 'currency' => 'TRY',
            'whatsapp_number' => '905551234567',
        ]);
        $category = Category::factory()->for($this->shop)->create();
        $this->coffee = Product::factory()->for($category)->create([
            'shop_id' => $this->shop->id, 'name' => ['en' => 'Turkish coffee'], 'price' => 85,
        ]);
    }

    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => ['en' => 'Coffee House'],
            'slug' => 'kahve-evi',
            'brand_color' => '#55642f',
            'currency' => 'TRY',
            'enabled_locales' => ['en'],
            'default_locale' => 'en',
            'order_types' => ['delivery', 'pickup'],
        ], $overrides);
    }

    private function order(array $overrides = [])
    {
        return $this->postJson('/kahve-evi/order', array_merge([
            'lang' => 'en',
            'items' => [['id' => $this->coffee->id, 'qty' => 2]],
            'type' => 'delivery', 'name' => 'Layla', 'address' => 'Main street 1',
        ], $overrides));
    }

    public function test_owner_saves_location_hours_and_delivery_settings(): void
    {
        $this->signInOwner($this->shop);

        $this->put(route('settings.update'), $this->settingsPayload([
            'address' => ['en' => 'Istiklal St 12', 'ar' => 'شارع الاستقلال'],
            'hours' => ['en' => 'Daily 8-23'],
            'maps_url' => 'https://maps.app.goo.gl/abc123',
            'instagram' => 'https://www.instagram.com/kahveevi/?hl=en',
            'delivery_fee' => '15,50',
            'min_order' => '120',
        ]))->assertSessionHasNoErrors();

        $shop = $this->shop->refresh();
        $this->assertSame('Istiklal St 12', $shop->getTranslation('address', 'en'));
        $this->assertSame('Daily 8-23', $shop->getTranslation('hours', 'en'));
        $this->assertSame('kahveevi', $shop->instagram);
        $this->assertSame('15.50', $shop->delivery_fee);
        $this->assertSame('120.00', $shop->min_order);
    }

    public function test_instagram_at_sign_is_stripped_and_blank_fees_become_null(): void
    {
        $this->signInOwner($this->shop);

        $this->put(route('settings.update'), $this->settingsPayload(['instagram' => '@kahve.evi', 'delivery_fee' => '', 'min_order' => '']))
            ->assertSessionHasNoErrors();

        $shop = $this->shop->refresh();
        $this->assertSame('kahve.evi', $shop->instagram);
        $this->assertNull($shop->delivery_fee);
        $this->assertNull($shop->min_order);
    }

    public function test_unsafe_links_and_bad_numbers_are_rejected(): void
    {
        $this->signInOwner($this->shop);

        $this->put(route('settings.update'), $this->settingsPayload(['maps_url' => 'javascript:alert(1)']))->assertSessionHasErrors('maps_url');
        $this->put(route('settings.update'), $this->settingsPayload(['instagram' => 'not a handle!']))->assertSessionHasErrors('instagram');
        $this->put(route('settings.update'), $this->settingsPayload(['delivery_fee' => '-5']))->assertSessionHasErrors('delivery_fee');
    }

    public function test_menu_shows_the_visit_us_card(): void
    {
        $this->shop->replaceTranslations('address', ['en' => 'Istiklal St 12']);
        $this->shop->replaceTranslations('hours', ['en' => 'Daily 8-23']);
        $this->shop->fill(['maps_url' => 'https://maps.app.goo.gl/abc123', 'instagram' => 'kahveevi'])->save();

        $this->get('/kahve-evi?lang=en')
            ->assertOk()
            ->assertSee('Istiklal St 12')
            ->assertSee('Daily 8-23')
            ->assertSee('https://maps.app.goo.gl/abc123', false)
            ->assertSee('https://instagram.com/kahveevi', false);
    }

    public function test_menu_without_info_has_no_visit_card(): void
    {
        $this->shop->forceFill(['whatsapp_number' => null])->save();

        $this->get('/kahve-evi?lang=en')->assertOk()->assertDontSee(__('shopinfo.menu.visit_title', [], 'en'));
    }

    public function test_search_box_appears_only_on_bigger_menus(): void
    {
        $this->get('/kahve-evi?lang=en')->assertDontSee('data-search-box', false);

        Product::factory()->count(8)->for($this->coffee->category)->create(['shop_id' => $this->shop->id]);

        $this->get('/kahve-evi?lang=en')->assertSee('data-search-box', false);
    }

    public function test_delivery_fee_is_added_to_the_whatsapp_total(): void
    {
        $this->shop->update(['delivery_fee' => 15]);

        $message = $this->order()->assertOk()->json('url');
        $text = urldecode($message);

        $this->assertStringContainsString('Delivery fee: ₺15', $text);
        $this->assertStringContainsString('Total: ₺185', $text); // 2 x 85 + 15
    }

    public function test_pickup_orders_pay_no_delivery_fee(): void
    {
        $this->shop->update(['delivery_fee' => 15]);

        $text = urldecode($this->order(['type' => 'pickup'])->assertOk()->json('url'));

        $this->assertStringNotContainsString('Delivery fee', $text);
        $this->assertStringContainsString('Total: ₺170', $text);
    }

    public function test_delivery_below_the_minimum_is_refused_but_pickup_is_not(): void
    {
        $this->shop->update(['min_order' => 200]);

        $this->order()->assertUnprocessable()->assertJsonPath('message', 'The minimum for delivery is ₺200.');
        $this->order(['type' => 'pickup'])->assertOk();
        $this->order(['items' => [['id' => $this->coffee->id, 'qty' => 3]]])->assertOk(); // 255 >= 200
    }
}
