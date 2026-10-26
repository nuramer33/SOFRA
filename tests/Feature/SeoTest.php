<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Support\ImageStore;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function shopWithProduct(array $attributes = [], ?string $imagePath = null): Shop
    {
        $shop = Shop::factory()->create($attributes + ['slug' => 'kahve-evi', 'default_locale' => 'en']);
        $category = Category::factory()->for($shop)->create();
        Product::factory()->for($category)->create(['shop_id' => $shop->id, 'image_path' => $imagePath]);

        return $shop;
    }

    private function productPhoto(Shop $shop): string
    {
        return app(ImageStore::class)->store(UploadedFile::fake()->image('dish.jpg', 900, 700), $shop, 'product');
    }

    public function test_landing_page_has_canonical_social_preview_and_structured_data(): void
    {
        $response = $this->withHeader('Accept-Language', 'en')->get('/')->assertOk();

        $response->assertSee('<link rel="canonical" href="'.route('home').'">', false)
            ->assertSee('<meta property="og:image" content="'.asset('og-default.png').'">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('"@type":"SoftwareApplication"', false)
            ->assertSee('"price":"0"', false)
            ->assertSee('"priceCurrency":"USD"', false);
    }

    public function test_menu_has_language_alternates_share_image_and_business_data(): void
    {
        $shop = $this->shopWithProduct(['whatsapp_number' => '905551234567', 'instagram' => 'kahveevi']);
        $shop->replaceTranslations('address', ['en' => 'Istiklal St 12']);
        $shop->save();

        $this->get('/kahve-evi?lang=en')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('menu.show', [$shop, 'lang' => 'en']).'">', false)
            ->assertSee('hreflang="tr"', false)
            ->assertSee('/kahve-evi/share.jpg?v=', false)
            ->assertSee('"@type":"LocalBusiness"', false)
            ->assertSee('"streetAddress":"Istiklal St 12"', false)
            ->assertSee('https://instagram.com/kahveevi', false);
    }

    public function test_shop_text_cannot_break_out_of_the_structured_data_script(): void
    {
        $shop = $this->shopWithProduct();
        $shop->replaceTranslations('name', ['en' => '</script><script>alert(1)</script>']);
        $shop->save();

        $this->get('/kahve-evi?lang=en')->assertOk()->assertDontSee('</script><script>alert(1)', false);
    }

    public function test_share_image_uses_a_product_photo_and_is_rebuilt_when_it_changes(): void
    {
        $shop = $this->shopWithProduct(imagePath: null);
        $this->get(route('menu.share-image', $shop))->assertOk()->assertHeader('Content-Type', 'image/jpeg'); // brand-color fallback

        $files = fn () => Storage::disk('public')->files("shops/{$shop->id}/share");
        $this->assertCount(1, $files());
        $first = $files()[0];

        Product::withoutGlobalScopes()->where('shop_id', $shop->id)->update(['image_path' => $this->productPhoto($shop)]);
        $this->get(route('menu.share-image', $shop))->assertOk();

        $this->assertCount(1, $files()); // the old version was removed
        $this->assertNotSame($first, $files()[0]);

        [$width, $height] = getimagesize(Storage::disk('public')->path($files()[0]));
        $this->assertSame([1200, 630], [$width, $height]);
    }

    public function test_share_image_uses_the_logo_when_there_is_no_product_photo(): void
    {
        $shop = $this->shopWithProduct();
        $shop->logo_path = app(ImageStore::class)->store(UploadedFile::fake()->image('logo.png', 300, 300), $shop, 'logo');
        $shop->save();

        $this->get(route('menu.share-image', $shop))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_inactive_shop_has_no_share_image(): void
    {
        $shop = $this->shopWithProduct(['is_active' => false]);

        $this->get(route('menu.share-image', $shop))->assertNotFound();
    }

    public function test_sitemap_lists_live_menus_with_language_alternates(): void
    {
        $live = $this->shopWithProduct();
        $this->shopWithProduct(['slug' => 'closed-shop', 'is_active' => false]);
        Shop::factory()->create(['slug' => 'empty-shop']); // nothing to show yet

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('menu.show', $live), false)
            ->assertSee('hreflang="ar"', false)
            ->assertSee(route('home'), false)
            ->assertDontSee('closed-shop')
            ->assertDontSee('empty-shop');
    }

    public function test_robots_blocks_private_areas_and_points_to_the_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /dashboard')
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_private_pages_are_marked_noindex_but_public_ones_are_not(): void
    {
        $this->get('/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/')->assertHeaderMissing('X-Robots-Tag');

        $this->signInOwner();
        $this->get(route('dashboard'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
}
