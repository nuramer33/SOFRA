<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Tenancy\CurrentShop;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use LogicException;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Shop $shopA;

    private Shop $shopB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shopA = Shop::factory()->create();
        $this->shopB = Shop::factory()->create();

        Product::factory()->count(3)->for(Category::factory()->for($this->shopA))->create(['shop_id' => $this->shopA->id]);
        Product::factory()->count(2)->for(Category::factory()->for($this->shopB))->create(['shop_id' => $this->shopB->id]);
    }

    public function test_queries_only_return_the_current_shops_rows(): void
    {
        app(CurrentShop::class)->set($this->shopA);

        $this->assertSame(3, Product::count());
        $this->assertSame(1, Category::count());
        $this->assertTrue(Product::all()->every(fn ($p) => $p->shop_id === $this->shopA->id));
    }

    public function test_finding_another_shops_row_by_id_returns_nothing(): void
    {
        $foreign = Product::withoutGlobalScopes()->where('shop_id', $this->shopB->id)->first();

        app(CurrentShop::class)->set($this->shopA);

        $this->assertNull(Product::find($foreign->id));
    }

    public function test_new_rows_always_get_the_current_shop_id(): void
    {
        app(CurrentShop::class)->set($this->shopA);

        // Even if someone tries to force another shop's id in…
        $category = new Category(['name' => ['ar' => 'x', 'tr' => 'x', 'en' => 'x']]);
        $category->shop_id = $this->shopB->id;
        $category->save();

        // …it ends up in the current shop.
        $this->assertSame($this->shopA->id, $category->fresh()->shop_id);
    }

    public function test_shop_id_is_not_mass_assignable(): void
    {
        $this->assertNotContains('shop_id', (new Product)->getFillable());
        $this->assertNotContains('shop_id', (new Category)->getFillable());
        $this->assertNotContains('shop_id', (new User)->getFillable());
    }

    public function test_a_row_cannot_be_moved_to_another_shop(): void
    {
        $category = Category::withoutGlobalScopes()->where('shop_id', $this->shopA->id)->first();

        $this->expectException(LogicException::class);

        $category->shop_id = $this->shopB->id;
        $category->save();
    }

    public function test_the_database_rejects_a_product_pointing_at_another_shops_category(): void
    {
        $foreignCategory = Category::withoutGlobalScopes()->where('shop_id', $this->shopB->id)->first();

        app(CurrentShop::class)->set($this->shopA);

        $this->expectException(QueryException::class);

        Product::create([
            'category_id' => $foreignCategory->id,
            'name' => ['ar' => 'x', 'tr' => 'x', 'en' => 'x'],
            'price' => 10,
        ]);
    }

    public function test_creating_without_any_shop_fails_closed(): void
    {
        $this->expectException(QueryException::class);

        Category::create(['name' => ['ar' => 'x', 'tr' => 'x', 'en' => 'x']]);
    }

    public function test_dashboard_only_shows_the_owners_own_shop(): void
    {
        $owner = User::factory()->for($this->shopA)->create();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($this->shopA->slug)
            ->assertDontSee($this->shopB->slug)
            ->assertViewHas('productCount', 3);
    }
}
