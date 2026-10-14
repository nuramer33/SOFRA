<?php

namespace Tests\Feature\Http\Controllers\Dashboard;

use App\Models\Shop;
use App\Models\User;
use chillerlan\QRCode\QRCode;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class QrCodeControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->signInOwner(Shop::factory()->create(['slug' => 'my-cafe']));
    }

    public function test_page_shows_a_qr_for_the_menu_link(): void
    {
        $this->get(route('qr.show'))->assertOk()->assertSee('<svg', false)->assertSee('my-cafe');
    }

    public function test_downloads_svg_and_png(): void
    {
        $this->get(route('qr.download', 'svg'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertDownload('menu-qr-my-cafe.svg');

        $png = $this->get(route('qr.download', 'png'))->assertOk()->assertHeader('Content-Type', 'image/png');
        [$width] = getimagesizefromstring($png->getContent());
        $this->assertGreaterThanOrEqual(800, $width);
    }

    /**
     * Rounded dots + brand colors are pretty; this proves they still scan.
     */
    #[TestWith(['#a8462a'])]
    #[TestWith(['#f4d03f'])] // a light brand color gets darkened for contrast
    public function test_png_scans_back_to_the_menu_link(string $brand): void
    {
        $this->owner->shop->update(['brand_color' => $brand]);

        $png = $this->get(route('qr.download', 'png'))->getContent();

        $this->assertSame(route('menu.show', 'my-cafe'), (string) (new QRCode)->readFromBlob($png));
    }

    public function test_downloading_completes_the_share_step(): void
    {
        $this->assertNull($this->owner->shop->qr_downloaded_at);

        $this->get(route('qr.print'))->assertOk();

        $this->assertNotNull($this->owner->shop->refresh()->qr_downloaded_at);
    }
}
