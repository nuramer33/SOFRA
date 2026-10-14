<?php

namespace Tests\Unit\Support;

use App\Support\Color;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class ColorTest extends TestCase
{
    #[TestWith(['#a8462a'])] // terracotta: already readable
    #[TestWith(['#f4d03f'])] // yellow: needs darkening
    #[TestWith(['#ffffff'])] // white: becomes ink
    #[TestWith(['#e3e6d3'])] // pale olive
    public function test_readable_on_paper_always_reaches_aa_contrast(string $brand): void
    {
        $this->assertGreaterThanOrEqual(4.5, Color::contrast(Color::readableOn($brand), Color::PAPER));
    }

    public function test_readable_on_keeps_a_color_that_is_already_readable(): void
    {
        $this->assertSame('#a8462a', Color::readableOn('#a8462a'));
    }
}
