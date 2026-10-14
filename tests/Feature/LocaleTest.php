<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_arabic_rtl_is_the_default(): void
    {
        $this->get(route('login'))
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('app.auth.login_title', [], 'ar'));
    }

    public function test_switching_language_changes_text_and_direction(): void
    {
        $this->from(route('login'))->get(route('locale.switch', 'tr'))->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertSee('<html lang="tr" dir="ltr">', false)
            ->assertSee('Tekrar hoş geldiniz');
    }

    public function test_phone_language_is_used_when_supported(): void
    {
        $this->withHeader('Accept-Language', 'tr-TR,tr;q=0.9,en;q=0.8')
            ->get(route('login'))
            ->assertSee('<html lang="tr" dir="ltr">', false);
    }

    public function test_unsupported_phone_language_falls_back_to_arabic(): void
    {
        $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')
            ->get(route('login'))
            ->assertSee('<html lang="ar" dir="rtl">', false);
    }

    public function test_manual_choice_beats_phone_language(): void
    {
        $this->get(route('locale.switch', 'en'));

        $this->withHeader('Accept-Language', 'tr-TR,tr;q=0.9')
            ->get(route('login'))
            ->assertSee('<html lang="en" dir="ltr">', false);
    }

    public function test_unsupported_languages_are_ignored(): void
    {
        $this->get('/locale/fr')->assertNotFound();
    }

    public function test_logged_in_owner_language_is_remembered(): void
    {
        $user = User::factory()->create(['locale' => 'ar']);

        $this->actingAs($user)->get(route('locale.switch', 'en'));

        $this->assertSame('en', $user->fresh()->locale);
    }
}
