<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $word = fake()->words(2, true);

        return [
            'category_id' => Category::factory(),
            // Always the same shop as the category (the DB enforces this too).
            'shop_id' => fn (array $attributes) => Category::withoutGlobalScopes()
                ->findOrFail($attributes['category_id'])->shop_id,
            'name' => ['ar' => $word, 'tr' => $word, 'en' => $word],
            'price' => fake()->randomFloat(2, 20, 400),
            'is_available' => true,
            'sort_order' => 0,
        ];
    }
}
