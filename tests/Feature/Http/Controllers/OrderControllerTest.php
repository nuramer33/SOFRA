<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Shop $shop;

    private Product $coffee;

    private Product $knafeh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::factory()->create([
            'slug' => 'kahve-evi',
            'name' => ['ar' => 'بيت القهوة', 'en' => 'Coffee House'],
            'default_locale' => 'ar',
            'whatsapp_number' => '905551234567',
            'currency' => 'TRY',
        ]);
        $category = Category::factory()->for($this->shop)->create();
        $this->coffee = Product::factory()->for($category)->create([
            'shop_id' => $this->shop->id, 'name' => ['ar' => 'قهوة تركية', 'en' => 'Turkish coffee'], 'price' => 85,
        ]);
        $this->knafeh = Product::factory()->for($category)->create([
            'shop_id' => $this->shop->id, 'name' => ['ar' => 'كنافة', 'en' => 'Knafeh'], 'price' => 140,
        ]);
    }

    private function order(array $overrides = [])
    {
        return $this->postJson('/kahve-evi/order', array_merge([
            'lang' => 'en',
            'items' => [['id' => $this->coffee->id, 'qty' => 2], ['id' => $this->knafeh->id, 'qty' => 1]],
            'type' => 'dine_in',
            'table' => '5',
        ], $overrides));
    }

    public function test_returns_a_whatsapp_link_with_the_order_in_the_shops_language(): void
    {
        $response = $this->order(['note' => 'بدون سكر'])->assertOk();

        $url = $response->json('url');
        $this->assertStringStartsWith('https://wa.me/905551234567?text=', $url);

        $message = rawurldecode(substr($url, strpos($url, '=') + 1));
        $this->assertStringContainsString('#'.$response->json('reference'), $message);
        $this->assertStringContainsString('2 × قهوة تركية  —  170 ₺', $message);   // Arabic: the owner's language
        $this->assertStringContainsString('1 × كنافة  —  140 ₺', $message);
        $this->assertStringContainsString('310 ₺', $message);                      // total from DB prices
        $this->assertStringContainsString('طاولة 5', $message);
        $this->assertStringContainsString('بدون سكر', $message);
        $this->assertStringNotContainsString('Turkish coffee', $message);
    }

    public function test_prices_come_from_the_database_not_the_browser(): void
    {
        $response = $this->order(['items' => [['id' => $this->coffee->id, 'qty' => 1, 'price' => 1]]])->assertOk();

        $this->assertStringContainsString(rawurlencode('85 ₺'), $response->json('url'));
    }

    public function test_sold_out_products_are_reported_back(): void
    {
        $this->knafeh->update(['is_available' => false]);

        $this->order()->assertUnprocessable()->assertJson(['unavailable' => [$this->knafeh->id]]);
    }

    public function test_another_shops_product_cannot_be_ordered(): void
    {
        $foreign = Product::factory()->create();

        $this->order(['items' => [['id' => $foreign->id, 'qty' => 1]]])
            ->assertUnprocessable()
            ->assertJson(['unavailable' => [$foreign->id]]);
    }

    public function test_delivery_requires_name_and_address(): void
    {
        $this->order(['type' => 'delivery'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'address']);
    }

    public function test_order_type_must_be_one_the_shop_offers(): void
    {
        $this->shop->update(['order_types' => ['pickup']]);

        $this->order(['type' => 'dine_in'])->assertJsonValidationErrors('type');
    }

    public function test_validation_messages_follow_the_customers_language(): void
    {
        $this->shop->update(['enabled_locales' => ['ar', 'en']]);

        $this->order(['type' => 'delivery', 'lang' => 'en'])
            ->assertJsonValidationErrors(['address' => 'Add your delivery address.']);
    }

    public function test_customer_text_cannot_inject_whatsapp_formatting(): void
    {
        $response = $this->order(['note' => "*FREE*\n_everything_"])->assertOk();

        $message = rawurldecode(substr($response->json('url'), strpos($response->json('url'), '=') + 1));
        $this->assertStringContainsString('FREE everything', $message);
        $this->assertStringNotContainsString('*FREE*', $message);
    }

    public function test_demo_shop_returns_a_preview_instead_of_a_whatsapp_link(): void
    {
        $this->shop->update(['slug' => config('sofra.demo_slug')]);

        $response = $this->postJson('/demo/order', [
            'items' => [['id' => $this->coffee->id, 'qty' => 1]],
            'type' => 'dine_in',
        ])->assertOk()->assertJson(['demo' => true]);

        $this->assertArrayNotHasKey('url', $response->json());
        $this->assertStringContainsString('قهوة تركية', $response->json('message'));
        $this->assertStringNotContainsString('*', $response->json('message'));
    }

    public function test_shops_without_whatsapp_cannot_receive_orders(): void
    {
        $this->shop->update(['whatsapp_number' => null]);

        $this->order()->assertNotFound();
    }
}
