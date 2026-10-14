<?php

namespace Tests\Feature\Http\Controllers\Dashboard;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShopSettingsControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->signInOwner(Shop::factory()->create(['slug' => 'my-cafe']));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => ['ar' => 'بيت القهوة', 'tr' => 'Kahve Evi', 'en' => ''],
            'tagline' => ['ar' => 'قهوة مختصة'],
            'slug' => 'my-cafe',
            'brand_color' => '#1F6F6B',
            'whatsapp_number' => '',
            'currency' => 'SAR',
            'enabled_locales' => ['tr', 'ar'],
            'default_locale' => 'ar',
            'order_types' => ['delivery', 'pickup'],
        ], $overrides);
    }

    public function test_page_renders(): void
    {
        $this->get(route('settings.edit'))->assertOk()->assertSee('my-cafe');
    }

    public function test_update_saves_identity_and_ordering_settings(): void
    {
        $this->put(route('settings.update'), $this->payload())
            ->assertRedirect(route('settings.edit'))
            ->assertSessionHasNoErrors();

        $shop = $this->owner->shop->refresh();
        $this->assertSame(['ar' => 'بيت القهوة', 'tr' => 'Kahve Evi'], $shop->getTranslations('name'));
        $this->assertSame('#1f6f6b', $shop->brand_color);
        $this->assertSame('SAR', $shop->currency);
        $this->assertSame(['ar', 'tr'], $shop->enabled_locales);
        $this->assertSame(['pickup', 'delivery'], $shop->order_types);
    }

    public function test_at_least_one_order_type_is_required(): void
    {
        $this->put(route('settings.update'), $this->payload(['order_types' => []]))
            ->assertSessionHasErrors('order_types');
    }

    public function test_whatsapp_number_is_normalized_to_international_digits(): void
    {
        $this->put(route('settings.update'), $this->payload(['whatsapp_number' => '+90 (555) 123 45 67']));

        $this->assertSame('905551234567', $this->owner->shop->refresh()->whatsapp_number);
    }

    public function test_whatsapp_number_without_country_code_is_rejected(): void
    {
        $this->put(route('settings.update'), $this->payload(['whatsapp_number' => '0555 123 45 67']))
            ->assertSessionHasErrors(['whatsapp_number' => __('app.settings.whatsapp_invalid')]);
    }

    public function test_main_language_must_be_enabled(): void
    {
        $this->put(route('settings.update'), $this->payload(['enabled_locales' => ['tr'], 'default_locale' => 'ar']))
            ->assertSessionHasErrors(['default_locale' => __('app.settings.default_locale_invalid')]);
    }

    public function test_slug_cannot_be_reserved_or_taken(): void
    {
        Shop::factory()->create(['slug' => 'taken']);

        $this->put(route('settings.update'), $this->payload(['slug' => 'dashboard']))->assertSessionHasErrors('slug');
        $this->put(route('settings.update'), $this->payload(['slug' => 'taken']))->assertSessionHasErrors('slug');
    }

    public function test_logo_can_be_uploaded_and_removed(): void
    {
        Storage::fake('public');

        $this->put(route('settings.update'), $this->payload(['logo' => UploadedFile::fake()->image('logo.png', 900, 900)]));
        $path = $this->owner->shop->refresh()->logo_path;
        Storage::disk('public')->assertExists($path);

        $this->put(route('settings.update'), $this->payload(['remove_logo' => '1']));

        $this->assertNull($this->owner->shop->refresh()->logo_path);
        Storage::disk('public')->assertMissing($path);
    }
}
