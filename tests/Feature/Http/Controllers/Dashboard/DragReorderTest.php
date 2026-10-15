<?php

namespace Tests\Feature\Http\Controllers\Dashboard;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DragReorderTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_categories_are_saved_in_the_dragged_order(): void
    {
        $owner = $this->signInOwner();
        [$first, $second, $third] = Category::factory()->count(3)->for($owner->shop)->create()->all();

        $this->putJson(route('categories.reorder'), ['ids' => [$third->id, $first->id, $second->id]])->assertOk();

        $this->assertSame(
            [$third->id, $first->id, $second->id],
            Category::query()->orderBy('sort_order')->pluck('id')->all(),
        );
    }

    public function test_unlisted_rows_keep_their_place_and_other_shops_are_untouched(): void
    {
        $owner = $this->signInOwner();
        [$first, $second, $third] = Category::factory()->count(3)->for($owner->shop)->create()->all();
        $foreign = Category::factory()->for(Shop::factory()->create())->create(['sort_order' => 7]);

        $this->putJson(route('categories.reorder'), ['ids' => [$third->id, $foreign->id]])->assertOk();

        $this->assertSame([$third->id, $first->id, $second->id], Category::query()->orderBy('sort_order')->pluck('id')->all());
        $this->assertSame(7, Category::withoutGlobalScopes()->find($foreign->id)->sort_order);
    }

    public function test_products_are_reordered_within_their_category(): void
    {
        $owner = $this->signInOwner();
        $category = Category::factory()->for($owner->shop)->create();
        [$a, $b] = Product::factory()->count(2)->for($category)->for($owner->shop)->create()->all();

        $this->putJson(route('products.reorder', $category), ['ids' => [$b->id, $a->id]])->assertOk();

        $this->assertSame([$b->id, $a->id], $category->products()->pluck('id')->all());
    }

    public function test_the_order_must_be_a_list_of_ids(): void
    {
        $this->signInOwner();

        $this->putJson(route('categories.reorder'), ['ids' => 'nope'])->assertUnprocessable();
    }

    public function test_guests_cannot_reorder(): void
    {
        $this->putJson(route('categories.reorder'), ['ids' => [1]])->assertUnauthorized();
    }
}
