<?php

namespace Tests\Feature\Dashboard;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MobileTabBarTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_today_is_a_tab_and_the_rest_sit_under_more(): void
    {
        $this->signInOwner();

        $html = $this->get(route('dashboard'))->assertOk()->getContent();
        $tabBar = substr($html, strpos($html, 'vt-tabbar'));

        $this->assertStringContainsString(route('today'), $tabBar);
        $this->assertStringContainsString(route('qr.show'), $tabBar);
        $this->assertStringContainsString(route('settings.edit'), $tabBar);
        $this->assertSame(1, substr_count($tabBar, 'grid-cols-5'));
    }
}
