<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LandingInstagramTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_landing_footer_links_to_the_instagram_account(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="https://instagram.com/norha_ndesign"', false)
            ->assertSee('@norha_ndesign');
    }
}
