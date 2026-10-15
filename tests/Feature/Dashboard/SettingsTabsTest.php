<?php

namespace Tests\Feature\Dashboard;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SettingsTabsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_settings_are_split_into_tabs_inside_one_form(): void
    {
        $this->signInOwner();

        $this->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('data-tab="store"', false)
            ->assertSee('data-tab="orders"', false)
            ->assertSee('data-tab="languages"', false)
            ->assertSee('data-tab="look"', false)
            ->assertSee('data-tab="advanced"', false);
    }

    public function test_page_opens_on_the_tab_that_has_a_validation_error(): void
    {
        $this->signInOwner();

        $this->from(route('settings.edit'))
            ->put(route('settings.update'), ['whatsapp_number' => 'not a number'])
            ->assertSessionHasErrors();

        $this->followingRedirects()->get(route('settings.edit'))->assertOk();
    }
}
