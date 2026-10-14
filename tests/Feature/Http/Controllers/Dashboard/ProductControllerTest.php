<?php

namespace Tests\Feature\Http\Controllers\Dashboard;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\ImageStore;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $owner;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->signInOwner();
        $this->category = Category::factory()->for($this->owner->shop)->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'category_id' => $this->category->id,
            'name' => ['ar' => 'قهوة تركية', 'tr' => 'Türk kahvesi'],
            'description' => ['ar' => 'بالهيل'],
            'price' => '85',
            'is_available' => '1',
            'is_featured' => '0',
        ], $overrides);
    }

    public function test_store_creates_the_product_in_the_owners_shop(): void
    {
        $this->post(route('products.store'), $this->payload())->assertRedirect();

        $product = Product::firstOrFail();
        $this->assertSame($this->owner->shop_id, $product->shop_id);
        $this->assertSame('85.00', $product->price);
        $this->assertSame('Türk kahvesi', $product->getTranslation('name', 'tr'));
        $this->assertTrue($product->is_available);
    }

    public function test_store_saves_the_photo_as_compressed_webp_in_two_sizes(): void
    {
        Storage::fake('public');

        $this->post(route('products.store'), $this->payload([
            'image' => UploadedFile::fake()->image('photo.jpg', 3000, 2000),
        ]))->assertRedirect();

        $path = Product::firstOrFail()->image_path;
        $this->assertStringStartsWith("shops/{$this->owner->shop_id}/product/", $path);
        $this->assertStringEndsWith('.webp', $path);

        [$width] = getimagesizefromstring(Storage::disk('public')->get($path));
        [$smallWidth] = getimagesizefromstring(Storage::disk('public')->get(ImageStore::variantPath($path, 'sm')));
        $this->assertSame(1200, $width);
        $this->assertSame(480, $smallWidth);
    }

    public function test_store_rejects_a_file_that_is_not_an_image(): void
    {
        Storage::fake('public');

        $this->post(route('products.store'), $this->payload([
            'image' => UploadedFile::fake()->create('evil.php.jpg', 10, 'application/x-php'),
        ]))->assertSessionHasErrors('image');

        $this->assertSame(0, Product::count());
    }

    public function test_store_rejects_another_shops_category(): void
    {
        $foreignCategory = Category::factory()->create();

        $this->post(route('products.store'), $this->payload(['category_id' => $foreignCategory->id]))
            ->assertSessionHasErrors('category_id');

        $this->assertSame(0, Product::withoutGlobalScopes()->count());
    }

    #[DataProvider('typedPrices')]
    public function test_store_accepts_prices_typed_on_arabic_and_turkish_keyboards(string $typed, string $stored): void
    {
        $this->post(route('products.store'), $this->payload(['price' => $typed]))->assertSessionHasNoErrors();

        $this->assertSame($stored, Product::firstOrFail()->price);
    }

    public static function typedPrices(): array
    {
        return [
            'arabic-indic digits' => ['١٢٫٥', '12.50'],
            'turkish comma' => ['12,50', '12.50'],
            'plain' => ['140', '140.00'],
        ];
    }

    public function test_compare_at_price_must_be_higher_than_the_price(): void
    {
        $this->post(route('products.store'), $this->payload(['price' => '100', 'compare_at_price' => '90']))
            ->assertSessionHasErrors('compare_at_price');
    }

    public function test_update_with_new_photo_deletes_the_old_files(): void
    {
        Storage::fake('public');
        $this->post(route('products.store'), $this->payload(['image' => UploadedFile::fake()->image('a.jpg', 600, 600)]));
        $product = Product::firstOrFail();
        $oldPath = $product->image_path;

        $this->put(route('products.update', $product), $this->payload(['image' => UploadedFile::fake()->image('b.jpg', 600, 600)]))
            ->assertRedirect();

        Storage::disk('public')->assertMissing([$oldPath, ImageStore::variantPath($oldPath, 'sm')]);
        Storage::disk('public')->assertExists($product->refresh()->image_path);
    }

    public function test_moving_to_another_category_places_the_product_last(): void
    {
        $product = Product::factory()->for($this->category)->create(['shop_id' => $this->owner->shop_id]);
        $other = Category::factory()->for($this->owner->shop)->create();
        Product::factory()->for($other)->create(['shop_id' => $this->owner->shop_id, 'sort_order' => 7]);

        $this->put(route('products.update', $product), $this->payload(['category_id' => $other->id]));

        $product->refresh();
        $this->assertSame($other->id, $product->category_id);
        $this->assertSame(8, $product->sort_order);
    }

    #[DataProvider('crossTenantRequests')]
    public function test_another_shops_product_returns_404(string $method, string $route): void
    {
        $foreign = Product::factory()->create();

        $this->call($method, route($route, $foreign), $this->payload(['direction' => 'up']))->assertNotFound();

        $this->assertModelExists($foreign);
    }

    public static function crossTenantRequests(): array
    {
        return [
            'edit' => ['GET', 'products.edit'],
            'update' => ['PUT', 'products.update'],
            'delete' => ['DELETE', 'products.destroy'],
            'move' => ['PATCH', 'products.position'],
            'availability' => ['PATCH', 'products.availability'],
        ];
    }

    public function test_availability_toggle_answers_json(): void
    {
        $product = Product::factory()->for($this->category)->create(['shop_id' => $this->owner->shop_id, 'is_available' => true]);

        $this->patchJson(route('products.availability', $product), ['is_available' => false])
            ->assertOk()
            ->assertExactJson(['is_available' => false]);

        $this->assertFalse($product->refresh()->is_available);
    }

    public function test_create_sends_shops_without_categories_to_create_one_first(): void
    {
        $this->signInOwner();

        $this->get(route('products.create'))->assertRedirect(route('categories.create'));
    }

    public function test_index_shows_prices_in_the_shops_currency(): void
    {
        Product::factory()->for($this->category)->create(['shop_id' => $this->owner->shop_id, 'price' => 85, 'name' => ['ar' => 'قهوة']]);

        $this->get(route('products.index'))->assertOk()->assertSee('85 ₺');
    }
}
