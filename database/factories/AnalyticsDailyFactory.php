<?php

namespace Database\Factories;

use App\Models\AnalyticsDaily;
use App\Models\Shop;
use App\Support\Analytics;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsDaily>
 */
class AnalyticsDailyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'day' => today()->toDateString(),
            'event' => Analytics::MENU_VIEW,
            'product_id' => 0,
            'total' => fake()->numberBetween(1, 40),
        ];
    }
}
