<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSuggestion;
use App\Models\Shop;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LuxeTemplateTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Shop $shop;

    private Category $category;

    private Product $oud;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::factory()->create([
            'slug' => 'atelier', 'template' => 'luxe', 'brand_color' => '#6b3a5e', 'default_locale' => 'ar',
            'enabled_locales' => ['ar', 'tr', 'en'], 'whatsapp_number' => '905551234567',
            'name' => ['ar' => 'أتيليه', 'tr' => 'Atölye', 'en' => 'Atelier'],
        ]);
        $this->category = Category::factory()->for($this->shop)->create(['name' => ['ar' => 'عطور', 'tr' => 'Parfüm', 'en' => 'Perfume']]);
        $this->oud = Product::factory()->for($this->category)->create([
            'shop_id' => $this->shop->id, 'price' => 1200, 'compare_at_price' => 1500, 'is_featured' => true,
            'name' => ['ar' => 'عود ملكي', 'tr' => 'Kraliyet Ud', 'en' => 'Royal oud'],
            'description' => ['ar' => 'عطر خشبي دافئ'],
        ]);
    }

    private function order(array $items)
    {
        return $this->postJson('/atelier/order', ['lang' => 'ar', 'type' => 'dine_in', 'table' => '1', 'items' => $items]);
    }

    public function test_a_shop_on_the_luxe_template_gets_the_luxe_page(): void
    {
        $this->get('/atelier')->assertOk()
            ->assertSee('theme-luxe', false)
            ->assertSee('--lx: #6b3a5e', false)
            ->assertSee('<html lang="ar" dir="rtl"', false)
            ->assertSee('عود ملكي')
            ->assertSee('1,200', false);
    }

    public function test_classic_shops_and_unknown_templates_keep_the_classic_page(): void
    {
        $this->shop->update(['template' => 'classic']);
        $this->get('/atelier')->assertOk()->assertDontSee('theme-luxe', false)->assertSee('عود ملكي');

        $this->shop->forceFill(['template' => 'does-not-exist'])->save();
        $this->get('/atelier')->assertOk()->assertDontSee('theme-luxe', false);
    }

    public function test_luxe_keeps_every_menu_feature_working(): void
    {
        $pouch = Product::factory()->for($this->category)->create(['shop_id' => $this->shop->id, 'price' => 100, 'name' => ['ar' => 'حافظة']]);
        ProductSuggestion::factory()->percent(10)->create(['shop_id' => $this->shop->id, 'product_id' => $this->oud->id, 'suggested_product_id' => $pouch->id]);

        $html = $this->get('/atelier')->assertOk()
            ->assertSee('x-ref="cartBar"', false)               // cart bar
            ->assertSee('id="menu-data"', false)                // product sheet data (incl. offers)
            ->assertSee('open-review', false)                   // rating dialog
            ->assertSee('data-section', false)
            ->getContent();
        $this->assertStringContainsString('"suggest"', $html);

        $this->order([['id' => $this->oud->id, 'qty' => 1], ['id' => $pouch->id, 'qty' => 1]])->assertOk();
    }

    public function test_a_sold_out_product_shows_as_sold_out_and_cannot_be_added(): void
    {
        $this->oud->update(['is_available' => false]);

        $this->get('/atelier')->assertOk()->assertSee(__('app.menu.sold_out'));
        $this->order([['id' => $this->oud->id, 'qty' => 1]])->assertStatus(422);
    }

    public function test_paused_or_closed_shops_show_the_notice_and_hide_the_cart(): void
    {
        $this->shop->update(['orders_paused' => true]);

        $this->get('/atelier')->assertOk()
            ->assertSee(__('quick.closed.paused'))
            ->assertDontSee('x-ref="cartBar"', false)
            ->assertDontSee('quickAdd', false);
    }

    public function test_an_empty_luxe_shop_shows_the_empty_state(): void
    {
        $empty = Shop::factory()->create(['slug' => 'soon', 'template' => 'luxe']);

        $this->get('/soon')->assertOk()->assertSee(__('app.menu.empty_title'));
    }

    public function test_the_owner_can_choose_the_template_but_only_a_real_one(): void
    {
        $owner = $this->signInOwner($this->shop);
        $payload = [
            'name' => ['ar' => 'أتيليه'], 'slug' => 'atelier', 'brand_color' => '#6b3a5e', 'currency' => 'TRY',
            'enabled_locales' => ['ar'], 'default_locale' => 'ar', 'order_types' => ['pickup'],
        ];

        $this->get(route('settings.edit'))->assertOk()->assertSee(__('templates.luxe.name'));

        $this->put(route('settings.update'), [...$payload, 'template' => 'classic'])->assertSessionHasNoErrors();
        $this->assertSame('classic', $owner->shop->refresh()->template);

        $this->put(route('settings.update'), [...$payload, 'template' => '../../etc/passwd'])->assertSessionHasErrors('template');
        $this->assertSame('classic', $owner->shop->refresh()->template);

        $this->put(route('settings.update'), $payload)->assertSessionHasNoErrors(); // omitting it keeps the current look
        $this->assertSame('classic', $owner->shop->refresh()->template);
    }
}
