<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_legal_pages_render_in_every_language(): void
    {
        foreach (['ar', 'tr', 'en'] as $locale) {
            $this->withHeader('Accept-Language', $locale);

            $this->get(route('legal.privacy'))->assertOk()->assertSee(__('legal.privacy.title', [], $locale));
            $this->get(route('legal.terms'))->assertOk()->assertSee(__('legal.terms.title', [], $locale));
        }
    }

    public function test_terms_page_shows_the_configured_price(): void
    {
        config(['sofra.billing.monthly_price' => '$12']);

        $this->withHeader('Accept-Language', 'en')->get(route('legal.terms'))->assertSee('$12 per month', false);
    }

    public function test_registration_requires_accepting_the_terms(): void
    {
        $this->post(route('register'), [
            'shop_name' => 'Cafe', 'slug' => 'cafe-one', 'name' => 'Sara', 'email' => 'sara@cafe.test',
            'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1',
        ])->assertSessionHasErrors('terms');

        $this->assertDatabaseMissing('users', ['email' => 'sara@cafe.test']);
    }

    public function test_registration_sends_a_confirmation_email(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'shop_name' => 'Cafe', 'slug' => 'cafe-one', 'name' => 'Sara', 'email' => 'sara@cafe.test',
            'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1', 'terms' => '1',
        ]);

        Notification::assertSentTo(User::where('email', 'sara@cafe.test')->firstOrFail(), VerifyEmail::class);
    }

    public function test_signed_link_confirms_the_email(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->actingAs($user)->get($url)->assertRedirect();

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_unsigned_confirmation_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))->assertForbidden();
    }

    public function test_banner_and_resend_for_unconfirmed_owner(): void
    {
        Notification::fake();
        $owner = $this->signInOwner();
        $owner->forceFill(['email_verified_at' => null])->save();

        $this->get(route('dashboard'))->assertSee(__('account.verify_title', [], 'ar'));
        $this->post(route('verification.send'))->assertRedirect();

        Notification::assertSentTo($owner, VerifyEmail::class);
    }

    public function test_confirmed_owner_sees_no_banner(): void
    {
        $this->signInOwner();

        $this->get(route('dashboard'))->assertDontSee(__('account.verify_title', [], 'ar'));
    }

    public function test_owner_can_update_name_and_changing_email_requires_reconfirming(): void
    {
        Notification::fake();
        $owner = $this->signInOwner();

        $this->put(route('account.update'), ['name' => 'New Name', 'email' => 'new@cafe.test'])->assertRedirect(route('account.edit'));

        $owner->refresh();
        $this->assertSame('New Name', $owner->name);
        $this->assertSame('new@cafe.test', $owner->email);
        $this->assertFalse($owner->hasVerifiedEmail());
        Notification::assertSentTo($owner, VerifyEmail::class);
    }

    public function test_email_must_stay_unique(): void
    {
        User::factory()->create(['email' => 'taken@cafe.test']);
        $this->signInOwner();

        $this->put(route('account.update'), ['name' => 'X', 'email' => 'taken@cafe.test'])->assertSessionHasErrors('email');
    }

    public function test_password_change_needs_the_current_password(): void
    {
        $owner = $this->signInOwner();

        $this->put(route('account.password'), [
            'current_password' => 'wrong', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass',
        ])->assertSessionHasErrors('current_password');

        $this->put(route('account.password'), [
            'current_password' => 'password', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass',
        ])->assertRedirect(route('account.edit'));

        $this->assertTrue(Hash::check('brand-new-pass', $owner->fresh()->password));
    }

    public function test_account_page_requires_login(): void
    {
        $this->get(route('account.edit'))->assertRedirect(route('login'));
    }

    public function test_reset_password_email_is_sent_in_the_owners_language(): void
    {
        Notification::fake();
        $user = User::factory()->create(['locale' => 'tr']);

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $mail = $notification->toMail($user);

            return $mail->subject === __('account.mail.reset_subject', [], 'tr');
        });
    }
}
