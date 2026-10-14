<?php

namespace Tests\Feature\Http\Controllers\Dashboard;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('categories.index'))->assertRedirect(route('login'));
    }

    public function test_index_lists_only_the_owners_categories(): void
    {
        $owner = $this->signInOwner();
        Category::factory()->for($owner->shop)->create(['name' => ['ar' => 'مشروبات']]);
        Category::factory()->create(['name' => ['ar' => 'فئة محل آخر']]);

        $this->get(route('categories.index'))
            ->assertOk()
            ->assertSee('مشروبات')
            ->assertDontSee('فئة محل آخر');
    }

    public function test_store_creates_category_at_the_end_with_translations(): void
    {
        $owner = $this->signInOwner();
        Category::factory()->for($owner->shop)->create(['sort_order' => 4]);

        $this->post(route('categories.store'), [
            'name' => ['ar' => 'حلويات', 'tr' => 'Tatlılar', 'en' => ''],
            'is_active' => '1',
        ])->assertRedirect(route('categories.index'));

        $category = Category::where('sort_order', 5)->firstOrFail();
        $this->assertSame($owner->shop_id, $category->shop_id);
        $this->assertSame(['ar' => 'حلويات', 'tr' => 'Tatlılar'], $category->getTranslations('name'));
    }

    public function test_store_requires_a_name_in_the_shops_main_language(): void
    {
        $this->signInOwner(Shop::factory()->create(['default_locale' => 'tr']));

        $this->post(route('categories.store'), ['name' => ['ar' => 'حلويات', 'tr' => '']])
            ->assertSessionHasErrors('name.tr');

        $this->assertSame(0, Category::count());
    }

    public function test_update_removes_a_cleared_translation(): void
    {
        $owner = $this->signInOwner();
        $category = Category::factory()->for($owner->shop)->create(['name' => ['ar' => 'قهوة', 'tr' => 'Kahve']]);

        $this->put(route('categories.update', $category), ['name' => ['ar' => 'قهوة', 'tr' => ''], 'is_active' => '0'])
            ->assertRedirect(route('categories.index'));

        $category->refresh();
        $this->assertSame(['ar' => 'قهوة'], $category->getTranslations('name'));
        $this->assertFalse($category->is_active);
    }

    /**
     * Proves the tenant is resolved before route model binding: another
     * shop's category must look like it doesn't exist (404, not 403).
     */
    #[DataProvider('crossTenantRequests')]
    public function test_another_shops_category_returns_404(string $method, string $route): void
    {
        $this->signInOwner();
        $foreign = Category::factory()->create();

        $this->call($method, route($route, $foreign), ['name' => ['ar' => 'x'], 'direction' => 'up'])
            ->assertNotFound();

        $this->assertModelExists($foreign);
    }

    public static function crossTenantRequests(): array
    {
        return [
            'edit' => ['GET', 'categories.edit'],
            'update' => ['PUT', 'categories.update'],
            'delete' => ['DELETE', 'categories.destroy'],
            'move' => ['PATCH', 'categories.position'],
        ];
    }

    public function test_destroy_deletes_its_products_and_their_image_files(): void
    {
        Storage::fake('public');
        $owner = $this->signInOwner();
        $category = Category::factory()->for($owner->shop)->create();
        $product = Product::factory()->for($category)->create(['shop_id' => $owner->shop_id]);

        $this->put(route('products.update', $product), [
            'category_id' => $category->id,
            'name' => ['ar' => 'منتج'],
            'price' => '10',
            'image' => UploadedFile::fake()->image('p.jpg', 800, 600),
        ]);
        $imagePath = $product->refresh()->image_path;
        Storage::disk('public')->assertExists($imagePath);

        $this->delete(route('categories.destroy', $category))->assertRedirect(route('categories.index'));

        $this->assertModelMissing($category);
        $this->assertModelMissing($product);
        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_move_up_swaps_with_the_previous_category(): void
    {
        $owner = $this->signInOwner();
        [$first, $second] = Category::factory()->for($owner->shop)->count(2)->sequence(['sort_order' => 0], ['sort_order' => 1])->create();

        $this->patch(route('categories.position', $second), ['direction' => 'up'])
            ->assertRedirect(route('categories.index').'#category-'.$second->id);

        $this->assertSame(0, $second->refresh()->sort_order);
        $this->assertSame(1, $first->refresh()->sort_order);
    }
}
