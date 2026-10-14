<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $word = fake()->unique()->word();

        return [
            'shop_id' => Shop::factory(),
            'name' => ['ar' => $word, 'tr' => $word, 'en' => $word],
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
