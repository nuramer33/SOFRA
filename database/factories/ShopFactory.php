<?php

namespace Database\Factories;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Shop>
 */
class ShopFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => ['ar' => $name, 'tr' => $name, 'en' => $name],
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'brand_color' => config('sofra.defaults.brand_color'),
            'currency' => 'TRY',
            'default_locale' => 'ar',
            'enabled_locales' => ['ar', 'tr', 'en'],
            'template' => 'classic',
            'whatsapp_number' => '90555'.fake()->numerify('#######'),
            'is_active' => true,
            'trial_ends_at' => now()->addMonths(2),
        ];
    }
}
