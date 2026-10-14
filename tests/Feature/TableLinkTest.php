<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Support\MenuQrCode;
use chillerlan\QRCode\QRCode;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TableLinkTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Shop $shop;

    private Product $coffee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::factory()->create(['slug' => 'kahve-evi', 'default_locale' => 'en', 'whatsapp_number' => '905551234567']);
        $this->coffee = Product::factory()->for(Category::factory()->for($this->shop))->create(['shop_id' => $this->shop->id, 'price' => 85]);
    }

    private function order(array $overrides = [])
    {
        return $this->postJson('/kahve-evi/order', [
            'lang' => 'en', 'type' => 'dine_in', 'items' => [['id' => $this->coffee->id, 'qty' => 1]], ...$overrides,
        ]);
    }

    private function message($response): string
    {
        return rawurldecode(substr($response->json('url'), strpos($response->json('url'), '=') + 1));
    }

    public function test_a_table_link_opens_the_same_menu_as_the_plain_link(): void
    {
        $this->get('/kahve-evi?table=5')->assertOk()->assertSee($this->coffee->name);
        $this->get('/kahve-evi')->assertOk();
    }

    public function test_the_table_number_reaches_the_order_message_and_can_be_left_out(): void
    {
        $this->assertStringContainsString('5', $this->message($this->order(['table' => '5'])->assertOk()));
        $this->order()->assertOk();
        $this->order(['table' => ''])->assertOk();
    }

    public function test_a_table_value_is_cleaned_before_it_reaches_the_chat_message(): void
    {
        $response = $this->order(['table' => "*5*\n_boom_ <b>1</b>"])->assertOk();

        $message = $this->message($response);
        $this->assertStringContainsString('Table 5boom b1b', $message);
        $this->assertStringNotContainsString('*5*', $message);
        $this->assertStringNotContainsString('<b>', $message);
    }

    public function test_a_table_value_that_is_too_long_is_rejected(): void
    {
        $this->order(['table' => str_repeat('7', 21)])->assertStatus(422)->assertJsonValidationErrors('table');
    }

    public function test_the_qr_always_points_to_the_plain_shop_link_and_nothing_else(): void
    {
        $qr = new MenuQrCode($this->shop);

        $this->assertSame(route('menu.show', 'kahve-evi'), $qr->url());
        $this->assertStringNotContainsString('?', $qr->url());
        $this->assertSame($qr->url(), (string) (new QRCode)->readFromBlob($qr->png()));
    }

    public function test_the_qr_page_offers_a_table_link_helper_not_per_table_codes(): void
    {
        $this->signInOwner($this->shop);

        $this->get(route('qr.show'))->assertOk()
            ->assertSee(__('qrtable.title'))
            ->assertSee('tableLink', false);
        $this->get(route('qr.print'))->assertOk()->assertSee('A6');
    }
}
