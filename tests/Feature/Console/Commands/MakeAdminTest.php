<?php

namespace Tests\Feature\Console\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MakeAdminTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_creates_a_new_admin_without_a_shop(): void
    {
        $this->artisan('sofra:admin', ['email' => 'Boss@Example.com'])
            ->expectsQuestion('Name', 'Amer')
            ->expectsQuestion('Password (min. 12 characters)', 'long-secret-123')
            ->assertSuccessful();

        $admin = User::where('email', 'boss@example.com')->firstOrFail();
        $this->assertTrue($admin->isAdmin());
        $this->assertNull($admin->shop_id);
        $this->assertTrue(Hash::check('long-secret-123', $admin->password));
    }

    public function test_promotes_an_existing_owner_only_after_confirmation(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);

        $this->artisan('sofra:admin', ['email' => 'owner@example.com'])
            ->expectsConfirmation('owner@example.com already exists (a shop owner). Give this account admin rights?', 'no')
            ->assertFailed();
        $this->assertFalse($owner->refresh()->isAdmin());

        $this->artisan('sofra:admin', ['email' => 'owner@example.com'])
            ->expectsConfirmation('owner@example.com already exists (a shop owner). Give this account admin rights?', 'yes')
            ->assertSuccessful();
        $this->assertTrue($owner->refresh()->isAdmin());
    }

    public function test_is_admin_cannot_be_set_through_registration(): void
    {
        $this->post(route('register'), [
            'shop_name' => 'Cafe', 'slug' => 'cafe', 'name' => 'Sneaky', 'email' => 'sneaky@example.com',
            'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1', 'is_admin' => 1, 'terms' => '1',
        ]);

        $this->assertFalse(User::where('email', 'sneaky@example.com')->firstOrFail()->isAdmin());
    }
}
