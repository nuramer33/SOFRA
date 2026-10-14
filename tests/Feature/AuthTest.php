<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function registration(array $overrides = []): array
    {
        return array_merge([
            'shop_name' => 'بيت القهوة',
            'slug' => 'coffee-house',
            'name' => 'سارة',
            'email' => 'sara@example.com',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
            'terms' => '1',
        ], $overrides);
    }

    public function test_pages_render(): void
    {
        foreach (['login', 'register', 'password.request'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_registration_creates_a_shop_and_its_owner(): void
    {
        $this->post(route('register'), $this->registration())
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        $shop = Shop::where('slug', 'coffee-house')->firstOrFail();
        $this->assertTrue($shop->trial_ends_at->isSameDay(now()->addDays(60)));
        $this->assertNotNull(User::where('email', 'sara@example.com')->first()->last_login_at);
        $this->assertSame('بيت القهوة', $shop->getTranslation('name', 'ar'));
        $this->assertSame('بيت القهوة', $shop->getTranslation('name', 'tr'));
        $this->assertSame($shop->id, User::where('email', 'sara@example.com')->first()->shop_id);
    }

    public function test_reserved_and_malformed_slugs_are_rejected(): void
    {
        foreach (['dashboard', 'login', 'ab', 'Has Space', '-dash', 'bad_char'] as $slug) {
            $this->post(route('register'), $this->registration(['slug' => $slug]))
                ->assertSessionHasErrors('slug');
        }

        $this->assertSame(0, Shop::count());
    }

    public function test_slug_must_be_unique(): void
    {
        Shop::factory()->create(['slug' => 'coffee-house']);

        $this->post(route('register'), $this->registration())->assertSessionHasErrors('slug');
    }

    public function test_owner_can_log_in_and_out(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);

        $this->post(route('login'), ['email' => 'owner@example.com', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->refresh()->last_login_at);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected_and_throttled(): void
    {
        User::factory()->create(['email' => 'owner@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), ['email' => 'owner@example.com', 'password' => 'nope'])
                ->assertSessionHasErrors('email');
        }

        // 6th try is locked out even with the right password.
        $this->post(route('login'), ['email' => 'owner@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_password_reset_link_does_not_reveal_unknown_emails(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $known = $this->post(route('password.email'), ['email' => $user->email]);
        $unknown = $this->post(route('password.email'), ['email' => 'nobody@example.com']);

        $known->assertSessionHas('status');
        $unknown->assertSessionHas('status');
        $unknown->assertSessionHasNoErrors();
        Notification::assertSentTo($user, ResetPassword::class);
    }
}
