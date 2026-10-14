<?php

namespace Tests\Unit\Models;

use App\Models\Shop;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ShopTest extends TestCase
{
    #[TestWith(['#a8462a', '#ffffff'])] // terracotta → white text
    #[TestWith(['#263a5c', '#ffffff'])] // navy → white text
    #[TestWith(['#f4d03f', '#1f2417'])] // yellow → dark text
    #[TestWith(['#ffffff', '#1f2417'])] // white → dark text
    public function test_brand_foreground_stays_readable(string $brand, string $expected): void
    {
        $this->assertSame($expected, (new Shop(['brand_color' => $brand]))->brandForeground());
    }

    public function test_content_locales_put_the_main_language_first(): void
    {
        $shop = new Shop(['default_locale' => 'en', 'enabled_locales' => ['ar', 'en']]);

        $this->assertSame(['en', 'ar'], $shop->contentLocales());
    }
}
