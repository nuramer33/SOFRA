<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ShopControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function signInAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        return $admin;
    }

    private function shopWithOwner(array $shop = [], array $owner = []): Shop
    {
        $model = Shop::factory()->create($shop);
        User::factory()->for($model)->create($owner);

        return $model;
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('admin.index'))->assertRedirect(route('login'));
    }

    #[TestWith(['admin.index'])]
    #[TestWith(['admin.shops.export'])]
    public function test_shop_owners_get_404_so_the_panel_stays_hidden(string $route): void
    {
        $this->signInOwner();

        $this->get(route($route))->assertNotFound();
    }

    public function test_owner_cannot_suspend_a_shop(): void
    {
        $this->signInOwner();
        $shop = Shop::factory()->create();

        $this->patch(route('admin.shops.status', $shop))->assertNotFound();
        $this->assertTrue($shop->refresh()->is_active);
    }

    public function test_lists_every_shop_with_owner_contacts(): void
    {
        $this->signInAdmin();
        $this->shopWithOwner(['slug' => 'cafe-one', 'whatsapp_number' => '905551112233'], ['email' => 'one@example.com']);
        $this->shopWithOwner(['slug' => 'cafe-two'], ['email' => 'two@example.com']);

        $this->get(route('admin.index'))
            ->assertOk()
            ->assertSee('one@example.com')
            ->assertSee('two@example.com')
            ->assertSee('+905551112233')
            ->assertViewHas('stats', fn (array $stats) => $stats['shops'] === 2);
    }

    public function test_search_finds_shops_by_owner_email(): void
    {
        $this->signInAdmin();
        $this->shopWithOwner(['slug' => 'cafe-one'], ['email' => 'findme@example.com']);
        $this->shopWithOwner(['slug' => 'cafe-two'], ['email' => 'other@example.com']);

        $this->get(route('admin.index', ['q' => 'findme']))
            ->assertSee('findme@example.com')
            ->assertDontSee('other@example.com');
    }

    public function test_filter_shows_shops_without_products(): void
    {
        $this->signInAdmin();
        $empty = $this->shopWithOwner(['slug' => 'empty-cafe']);
        $busy = $this->shopWithOwner(['slug' => 'busy-cafe']);
        Product::factory()->for(Category::factory()->for($busy))->create(['shop_id' => $busy->id]);

        $this->get(route('admin.index', ['status' => 'empty']))
            ->assertSee('/empty-cafe')
            ->assertDontSee('/busy-cafe');
    }

    public function test_suspending_takes_the_menu_offline_and_back(): void
    {
        $this->signInAdmin();
        $shop = $this->shopWithOwner(['slug' => 'cafe-one']);

        $this->patch(route('admin.shops.status', $shop))->assertRedirect();
        $this->assertFalse($shop->refresh()->is_active);
        $this->get('/cafe-one')->assertNotFound();

        $this->patch(route('admin.shops.status', $shop));
        $this->assertTrue($shop->refresh()->is_active);
        $this->get('/cafe-one')->assertOk();
    }

    public function test_details_page_shows_the_owner(): void
    {
        $this->signInAdmin();
        $shop = $this->shopWithOwner([], ['email' => 'owner@example.com', 'name' => 'سارة']);

        $this->get(route('admin.shops.show', $shop))->assertOk()->assertSee('owner@example.com')->assertSee('سارة');
    }

    public function test_csv_export_contains_contacts_and_neutralises_formulas(): void
    {
        $this->signInAdmin();
        $this->shopWithOwner(['name' => ['ar' => '=HYPERLINK("http://evil")', 'tr' => 'x', 'en' => 'x']], ['email' => 'one@example.com']);

        $response = $this->get(route('admin.shops.export'))->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('one@example.com', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString(',=HYPERLINK', $csv);
        $this->assertStringNotContainsString('password', strtolower($csv));
    }

    public function test_admin_without_a_shop_lands_on_the_admin_panel(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'boss@example.com']);

        $this->post(route('login'), ['email' => 'boss@example.com', 'password' => 'password'])
            ->assertRedirect(route('admin.index'));

        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('admin.index'));
    }
}
