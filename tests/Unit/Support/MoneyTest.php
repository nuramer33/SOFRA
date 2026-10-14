<?php

namespace Tests\Unit\Support;

use App\Support\Money;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    #[TestWith([85, 'TRY', 'ar', '85 ₺'])]
    #[TestWith([12.5, 'SAR', 'ar', '12.50 ر.س'])]
    #[TestWith([1500, 'SYP', 'ar', '1,500 ل.س'])]
    #[TestWith([12.5, 'TRY', 'tr', '12,50 ₺'])]
    #[TestWith([12.5, 'USD', 'en', '$12.50'])]
    #[TestWith([40, 'SAR', 'en', '40 SAR'])]
    public function test_formats_with_western_digits_and_local_symbols(float $amount, string $currency, string $locale, string $expected): void
    {
        $this->assertSame($expected, Money::format($amount, $currency, $locale));
    }
}
