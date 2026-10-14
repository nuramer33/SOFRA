<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MenuControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Shop $shop;

    private Category $drinks;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::factory()->create([
            'slug' => 'kahve-evi',
            'name' => ['ar' => 'بيت القهوة', 'tr' => 'Kahve Evi'],
            'default_locale' => 'ar',
            'enabled_locales' => ['ar', 'tr'],
            'brand_color' => '#1f6f6b',
        ]);
        $this->drinks = Category::factory()->for($this->shop)->create(['name' => ['ar' => 'مشروبات', 'tr' => 'İçecekler']]);
    }

    private function product(array $attributes = []): Product
    {
        return Product::factory()->for($this->drinks)->create(['shop_id' => $this->shop->id, ...$attributes]);
    }

    public function test_renders_the_menu_in_the_shops_main_language(): void
    {
        $this->product(['name' => ['ar' => 'قهوة تركية', 'tr' => 'Türk kahvesi'], 'price' => 85]);

        $this->get('/kahve-evi')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl" class="is-menu">', false)
            ->assertSee('بيت القهوة')
            ->assertSee('قهوة تركية')
            ->assertSee('85 ₺')
            ->assertDontSee('Türk kahvesi');
    }

    public function test_lang_parameter_switches_language_and_is_remembered(): void
    {
        $this->product(['name' => ['ar' => 'قهوة تركية', 'tr' => 'Türk kahvesi']]);

        $this->get('/kahve-evi?lang=tr')
            ->assertSee('<html lang="tr" dir="ltr" class="is-menu">', false)
            ->assertSee('Türk kahvesi');

        $this->get('/kahve-evi')->assertSee('Türk kahvesi');
    }

    public function test_language_the_shop_does_not_offer_falls_back_to_the_main_one(): void
    {
        $this->get('/kahve-evi?lang=en')->assertSee('<html lang="ar"', false);
    }

    public function test_missing_translation_shows_the_main_language_text(): void
    {
        $this->product(['name' => ['ar' => 'كنافة']]);

        $this->get('/kahve-evi?lang=tr')->assertSee('كنافة');
    }

    public function test_hidden_and_empty_categories_are_not_shown(): void
    {
        $this->product();
        Category::factory()->for($this->shop)->create(['name' => ['ar' => 'قسم مخفي'], 'is_active' => false]);
        Category::factory()->for($this->shop)->create(['name' => ['ar' => 'قسم فارغ']]);

        $this->get('/kahve-evi')->assertOk()->assertSee('مشروبات')->assertDontSee('قسم مخفي')->assertDontSee('قسم فارغ');
    }

    public function test_sold_out_products_are_shown_marked_as_sold_out(): void
    {
        $this->product(['name' => ['ar' => 'شاي'], 'is_available' => false]);

        $this->get('/kahve-evi')->assertSee('شاي')->assertSee(__('app.menu.sold_out', [], 'ar'));
    }

    public function test_never_shows_another_shops_products(): void
    {
        $this->product();
        $otherProduct = Product::factory()->create(['name' => ['ar' => 'منتج محل آخر']]);

        $this->get('/kahve-evi')->assertDontSee('منتج محل آخر');
        $this->get('/'.$otherProduct->shop->slug)->assertSee('منتج محل آخر');
    }

    public function test_brand_colors_are_applied_and_kept_readable(): void
    {
        $this->shop->update(['brand_color' => '#f4d03f']); // light yellow

        $this->get('/kahve-evi')
            ->assertSee('--brand: #f4d03f', false)
            ->assertSee('--brand-fg: #1f2417', false)
            ->assertDontSee('--brand-ink: #f4d03f', false);
    }

    public function test_shop_without_products_shows_a_coming_soon_message(): void
    {
        $this->get('/kahve-evi')->assertOk()->assertSee(__('app.menu.empty_title', [], 'ar'));
    }

    public function test_inactive_or_unknown_shops_return_404(): void
    {
        $this->shop->update(['is_active' => false]);

        $this->get('/kahve-evi')->assertNotFound();
        $this->get('/no-such-shop')->assertNotFound();
    }

    public function test_ordering_is_offered_only_when_the_shop_has_whatsapp(): void
    {
        $this->product();
        $cartLabel = __('app.cart.view', [], 'ar');

        $this->get('/kahve-evi')->assertSee($cartLabel);

        $this->shop->update(['whatsapp_number' => null]);
        $this->get('/kahve-evi')->assertDontSee($cartLabel);
    }

    public function test_app_routes_are_not_mistaken_for_shop_slugs(): void
    {
        $this->get('/login')->assertOk()->assertSee(__('app.auth.login_title', [], 'ar'));
    }
}
