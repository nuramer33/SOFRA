<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductSuggestion;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductSuggestion>
 */
class ProductSuggestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'product_id' => Product::factory(),
            'suggested_product_id' => Product::factory(),
            'title' => null,
            'description' => null,
            'discount_type' => null,
            'discount_value' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function percent(float $value): static
    {
        return $this->state(['discount_type' => ProductSuggestion::DISCOUNT_PERCENT, 'discount_value' => $value]);
    }

    public function fixed(float $value): static
    {
        return $this->state(['discount_type' => ProductSuggestion::DISCOUNT_FIXED, 'discount_value' => $value]);
    }
}
