<?php

namespace Tests;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Views use @vite; tests don't need built assets.
        $this->withoutVite();

        // Symfony's test requests claim an English browser; tests act as an Arabic phone.
        $this->withHeader('Accept-Language', 'ar');
    }

    /**
     * Log in as the owner of a (new or given) shop.
     */
    protected function signInOwner(?Shop $shop = null): User
    {
        $owner = User::factory()->for($shop ?? Shop::factory())->create();

        $this->actingAs($owner);

        return $owner;
    }
}
