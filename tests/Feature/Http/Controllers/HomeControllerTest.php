<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Shop;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_renders_the_landing_page_in_arabic_with_the_trial_offer(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('شهرين')                         // "two months", grammatically
            ->assertSee(route('register'));
    }

    public function test_links_the_demo_menu_only_when_it_exists(): void
    {
        $this->get('/')->assertDontSee('/demo"', false);

        Shop::factory()->create(['slug' => 'demo']);

        $this->get('/')->assertSee(route('menu.show', 'demo'));
    }

    public function test_landing_page_does_not_show_prices_while_the_service_is_free(): void
    {
        config(['sofra.billing.monthly_price' => '$9', 'sofra.billing.yearly_price' => '$90']);

        $this->get('/locale/en');
        $this->get('/')->assertDontSee('$9')->assertDontSee('$90');
    }

    public function test_logged_in_owner_is_sent_to_the_dashboard_not_sign_up(): void
    {
        $this->signInOwner();

        $this->get('/')->assertSee(route('dashboard'));
    }
}
