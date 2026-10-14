<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSuggestion;
use App\Models\Shop;
use App\Support\Offers;
use App\Support\WhatsAppOrder;
use App\Tenancy\CurrentShop;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ProductSuggestionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Shop $shop;

    private Category $category;

    private Product $perfume;

    private Product $pouch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::factory()->create([
            'slug' => 'atelier', 'default_locale' => 'en', 'enabled_locales' => ['en', 'ar'], 'whatsapp_number' => '905551234567',
        ]);
        $this->category = Category::factory()->for($this->shop)->create();
        $this->perfume = $this->product(['price' => 200]);
        $this->pouch = $this->product(['price' => 50]);
    }

    private function product(array $attributes = []): Product
    {
        return Product::factory()->for($this->category)->create(['shop_id' => $this->shop->id, ...$attributes]);
    }

    private function suggestion(array $state = [], ?Product $main = null, ?Product $item = null): ProductSuggestion
    {
        return ProductSuggestion::factory()->create([
            'shop_id' => $this->shop->id,
            'product_id' => ($main ?? $this->perfume)->id,
            'suggested_product_id' => ($item ?? $this->pouch)->id,
            ...$state,
        ]);
    }

    /** @return Collection<int, Product> */
    private function catalog(): Collection
    {
        return Product::withoutGlobalScopes()->where('shop_id', $this->shop->id)->get()->keyBy('id');
    }

    // ---- Pricing rules (server side) ------------------------------------

    public function test_a_percentage_applies_once_per_unit_of_the_main_product(): void
    {
        $this->suggestion(['discount_type' => 'percent', 'discount_value' => 20]);
        $suggestions = ProductSuggestion::withoutGlobalScopes()->get();

        // 1 perfume, 3 pouches: only one pouch is discounted (20% of 50 = 10).
        $discounts = Offers::discounts([$this->perfume->id => 1, $this->pouch->id => 3], $this->catalog(), $suggestions);

        $this->assertSame([$this->pouch->id => 10.0], $discounts);
    }

    public function test_no_discount_without_the_main_product_or_a_valid_value(): void
    {
        $this->suggestion(['discount_type' => 'percent', 'discount_value' => 20]);
        $suggestions = ProductSuggestion::withoutGlobalScopes()->get();

        $this->assertSame([], Offers::discounts([$this->pouch->id => 2], $this->catalog(), $suggestions));

        ProductSuggestion::withoutGlobalScopes()->update(['discount_value' => 95]); // above the 90% limit
        $this->assertSame([], Offers::discounts([$this->perfume->id => 1, $this->pouch->id => 1], $this->catalog(), ProductSuggestion::withoutGlobalScopes()->get()));

        ProductSuggestion::withoutGlobalScopes()->update(['discount_type' => null, 'discount_value' => null]);
        $this->assertSame([], Offers::discounts([$this->perfume->id => 1, $this->pouch->id => 1], $this->catalog(), ProductSuggestion::withoutGlobalScopes()->get()));
    }

    public function test_a_fixed_discount_never_exceeds_the_price_and_inactive_offers_are_ignored(): void
    {
        $this->suggestion(['discount_type' => 'fixed', 'discount_value' => 500]);

        $discounts = Offers::discounts([$this->perfume->id => 1, $this->pouch->id => 1], $this->catalog(), ProductSuggestion::withoutGlobalScopes()->get());
        $this->assertSame([$this->pouch->id => 50.0], $discounts);

        ProductSuggestion::withoutGlobalScopes()->update(['is_active' => false]);
        $this->assertSame([], Offers::discounts([$this->perfume->id => 1, $this->pouch->id => 1], $this->catalog(), ProductSuggestion::withoutGlobalScopes()->get()));
    }

    public function test_overlapping_offers_use_the_best_saving_first_and_never_count_a_unit_twice(): void
    {
        $watch = $this->product(['price' => 300]);
        $this->suggestion(['discount_type' => 'fixed', 'discount_value' => 5]);                       // perfume → pouch: 5
        $this->suggestion(['discount_type' => 'percent', 'discount_value' => 40], $watch, $this->pouch); // watch → pouch: 20

        $discounts = Offers::discounts(
            [$this->perfume->id => 1, $watch->id => 1, $this->pouch->id => 2],
            $this->catalog(),
            ProductSuggestion::withoutGlobalScopes()->get(),
        );

        $this->assertSame([$this->pouch->id => 25.0], $discounts); // 20 on one pouch + 5 on the other
    }

    public function test_the_whatsapp_order_uses_server_prices_and_shows_the_saving(): void
    {
        $this->suggestion(['discount_type' => 'percent', 'discount_value' => 20]);

        $order = WhatsAppOrder::make(
            $this->shop,
            $this->catalog(),
            [$this->perfume->id => 1, $this->pouch->id => 1],
            ['type' => 'pickup'],
            ProductSuggestion::withoutGlobalScopes()->get(),
        );

        $this->assertSame(240.0, $order->total());   // 200 + (50 - 10)
        $this->assertSame(10.0, $order->savings());
        $this->assertStringContainsString(__('suggestions.order.discount', [], 'en'), $order->message());
    }

    public function test_the_order_endpoint_applies_offers_only_when_both_products_are_ordered(): void
    {
        $this->suggestion(['discount_type' => 'fixed', 'discount_value' => 10]);

        $both = $this->postJson('/atelier/order', [
            'lang' => 'en', 'type' => 'dine_in', 'table' => '2',
            'items' => [['id' => $this->perfume->id, 'qty' => 1], ['id' => $this->pouch->id, 'qty' => 1]],
        ])->assertOk();
        $alone = $this->postJson('/atelier/order', [
            'lang' => 'en', 'type' => 'dine_in', 'table' => '2',
            'items' => [['id' => $this->pouch->id, 'qty' => 1]],
        ])->assertOk();

        $message = fn ($response) => rawurldecode(substr($response->json('url'), strpos($response->json('url'), '=') + 1));

        $this->assertStringContainsString(__('suggestions.order.discount', [], 'en'), $message($both));
        $this->assertStringNotContainsString(__('suggestions.order.discount', [], 'en'), $message($alone));
    }

    public function test_a_sold_out_suggested_product_is_rejected_like_any_other(): void
    {
        $this->suggestion(['discount_type' => 'fixed', 'discount_value' => 10]);
        $this->pouch->update(['is_available' => false]);

        $this->postJson('/atelier/order', [
            'lang' => 'en', 'type' => 'dine_in', 'table' => '2',
            'items' => [['id' => $this->perfume->id, 'qty' => 1], ['id' => $this->pouch->id, 'qty' => 1]],
        ])->assertStatus(422)->assertJsonPath('unavailable.0', $this->pouch->id);
    }

    // ---- Public menu ----------------------------------------------------

    private function menuData(): array
    {
        $html = $this->get('/atelier')->assertOk()->getContent();
        preg_match('#<script type="application/json" id="menu-data">(.*?)</script>#s', $html, $matches);

        return json_decode($matches[1], true);
    }

    public function test_the_menu_only_offers_orderable_products_and_valid_discounts(): void
    {
        $this->suggestion(['title' => ['en' => 'Add a pouch?'], 'discount_type' => 'percent', 'discount_value' => 20]);
        $data = $this->menuData();

        $this->assertSame($this->pouch->id, $data[$this->perfume->id]['suggest'][0]['item']);
        $this->assertSame('Add a pouch?', $data[$this->perfume->id]['suggest'][0]['title']);
        $this->assertEquals(['type' => 'percent', 'value' => 20], $data[$this->perfume->id]['suggest'][0]['discount']);

        // An out-of-range discount is never advertised, but the suggestion still shows.
        ProductSuggestion::withoutGlobalScopes()->update(['discount_value' => 99]);
        $this->assertNull($this->menuData()[$this->perfume->id]['suggest'][0]['discount']);

        $this->pouch->update(['is_available' => false]);
        $this->assertSame([], $this->menuData()[$this->perfume->id]['suggest']);

        $this->pouch->update(['is_available' => true]);
        ProductSuggestion::withoutGlobalScopes()->update(['is_active' => false]);
        $this->assertSame([], $this->menuData()[$this->perfume->id]['suggest']);
    }

    // ---- Dashboard ------------------------------------------------------

    private function payload(array $row = []): array
    {
        return ['suggestions' => [[
            'suggested_product_id' => $this->pouch->id,
            'title' => ['en' => 'Add a pouch?', 'ar' => 'أضف حقيبة؟'],
            'description' => ['en' => 'Matches this scent'],
            'discount_type' => 'percent',
            'discount_value' => '15',
            'is_active' => '1',
            ...$row,
        ]]];
    }

    public function test_guests_cannot_manage_suggestions(): void
    {
        $this->get(route('products.suggestions.edit', $this->perfume))->assertRedirect(route('login'));
        $this->put(route('products.suggestions.update', $this->perfume), $this->payload())->assertRedirect(route('login'));
    }

    public function test_the_owner_can_save_and_see_suggestions(): void
    {
        $this->signInOwner($this->shop);

        $this->get(route('products.suggestions.edit', $this->perfume))->assertOk();

        $this->put(route('products.suggestions.update', $this->perfume), $this->payload())
            ->assertRedirect(route('products.suggestions.edit', $this->perfume))
            ->assertSessionHasNoErrors();

        $saved = ProductSuggestion::withoutGlobalScopes()->sole();
        $this->assertSame($this->shop->id, $saved->shop_id);
        $this->assertSame($this->pouch->id, $saved->suggested_product_id);
        $this->assertSame(['en' => 'Add a pouch?', 'ar' => 'أضف حقيبة؟'], $saved->getTranslations('title'));
        $this->assertSame('15.00', $saved->discount_value);

        $this->get(route('products.suggestions.edit', $this->perfume))->assertOk()->assertSee('Add a pouch?');

        // Saving an empty list clears them.
        $this->put(route('products.suggestions.update', $this->perfume), ['suggestions' => []])->assertSessionHasNoErrors();
        $this->assertSame(0, ProductSuggestion::withoutGlobalScopes()->count());
    }

    public function test_validation_rejects_bad_discounts_and_missing_main_language(): void
    {
        $this->signInOwner($this->shop);
        $url = route('products.suggestions.update', $this->perfume);

        $this->put($url, $this->payload(['discount_value' => '95']))->assertSessionHasErrors('suggestions.0.discount_value');
        $this->put($url, $this->payload(['discount_type' => 'fixed', 'discount_value' => '50']))->assertSessionHasErrors('suggestions.0.discount_value'); // = price
        $this->put($url, $this->payload(['discount_type' => 'fixed', 'discount_value' => '-3']))->assertSessionHasErrors('suggestions.0.discount_value');
        $this->put($url, $this->payload(['discount_type' => 'weird']))->assertSessionHasErrors('suggestions.0.discount_type');
        $this->put($url, $this->payload(['title' => ['ar' => 'أضف حقيبة؟']]))->assertSessionHasErrors('suggestions.0.title.en'); // main language missing

        $this->assertSame(0, ProductSuggestion::withoutGlobalScopes()->count());
    }

    public function test_a_product_cannot_suggest_itself_or_another_shops_product(): void
    {
        $this->signInOwner($this->shop);
        $other = Shop::factory()->create();
        $foreign = Product::factory()->for(Category::factory()->for($other))->create(['shop_id' => $other->id]);

        $this->put(route('products.suggestions.update', $this->perfume), $this->payload(['suggested_product_id' => $this->perfume->id]))
            ->assertSessionHasErrors('suggestions.0.suggested_product_id');
        $this->put(route('products.suggestions.update', $this->perfume), $this->payload(['suggested_product_id' => $foreign->id]))
            ->assertSessionHasErrors('suggestions.0.suggested_product_id');
    }

    public function test_an_owner_cannot_open_or_change_another_shops_suggestions(): void
    {
        $this->signInOwner(Shop::factory()->create());

        $this->get(route('products.suggestions.edit', $this->perfume))->assertNotFound();
        $this->put(route('products.suggestions.update', $this->perfume), $this->payload())->assertNotFound();

        $this->assertSame(0, ProductSuggestion::withoutGlobalScopes()->count());
    }

    public function test_deleting_a_product_removes_its_suggestions(): void
    {
        $this->suggestion();
        app(CurrentShop::class)->set($this->shop);

        $this->pouch->delete();

        $this->assertSame(0, ProductSuggestion::withoutGlobalScopes()->count());
    }
}
