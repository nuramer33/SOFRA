<?php

namespace Tests\Feature;

use App\Mail\ReviewReceived;
use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Shop $shop;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Mail::fake();

        $this->shop = Shop::factory()->create(['slug' => 'kahve-evi']);
        $this->owner = User::factory()->for($this->shop)->create(['email' => 'owner@kahve.test']);
    }

    public function test_customer_can_rate_the_shop_and_the_owner_is_emailed(): void
    {
        $this->postJson('/kahve-evi/review', [
            'rating' => 4,
            'comment' => 'Lovely place',
            'name' => 'Layla',
            'contact' => '+905551112233',
            'lang' => 'en',
        ])->assertCreated();

        $review = Review::withoutGlobalScopes()->sole();
        $this->assertSame($this->shop->id, $review->shop_id);
        $this->assertSame(4, $review->rating);
        $this->assertSame('Layla', $review->name);

        Mail::assertSent(ReviewReceived::class, fn (ReviewReceived $mail) => $mail->hasTo('owner@kahve.test')
            && $mail->review->is($review));
    }

    public function test_photo_is_re_encoded_stored_and_attached_to_the_email(): void
    {
        $this->postJson('/kahve-evi/review', [
            'rating' => 5,
            'photo' => UploadedFile::fake()->image('dish.jpg', 800, 600),
        ])->assertCreated();

        $review = Review::withoutGlobalScopes()->sole();
        $this->assertStringStartsWith("shops/{$this->shop->id}/review/", $review->photo_path);
        $this->assertStringEndsWith('.webp', $review->photo_path);
        Storage::disk('public')->assertExists($review->photo_path);

        Mail::assertSent(ReviewReceived::class, fn (ReviewReceived $mail) => count($mail->attachments()) === 1);
    }

    public function test_only_a_rating_from_one_to_five_is_required(): void
    {
        $this->postJson('/kahve-evi/review', ['rating' => 5])->assertCreated();

        foreach ([0, 6, 'x', null] as $invalid) {
            $this->postJson('/kahve-evi/review', ['rating' => $invalid])->assertUnprocessable()->assertJsonValidationErrors('rating');
        }
    }

    public function test_non_images_are_rejected(): void
    {
        $this->postJson('/kahve-evi/review', [
            'rating' => 3,
            'photo' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors('photo');
    }

    public function test_filled_honeypot_is_rejected(): void
    {
        $this->postJson('/kahve-evi/review', ['rating' => 5, 'website' => 'http://spam.test'])->assertUnprocessable();

        $this->assertSame(0, Review::withoutGlobalScopes()->count());
    }

    public function test_inactive_shop_cannot_receive_reviews(): void
    {
        $this->shop->update(['is_active' => false]);

        $this->postJson('/kahve-evi/review', ['rating' => 5])->assertNotFound();
    }

    public function test_review_submissions_are_rate_limited(): void
    {
        foreach (range(1, 5) as $ignored) {
            $this->postJson('/kahve-evi/review', ['rating' => 5])->assertCreated();
        }

        $this->postJson('/kahve-evi/review', ['rating' => 5])->assertTooManyRequests();
    }

    public function test_owner_sees_only_their_own_shops_reviews(): void
    {
        $mine = Review::factory()->for($this->shop)->create(['comment' => 'mine-comment']);
        Review::factory()->create(['comment' => 'foreign-comment']);

        $this->actingAs($this->owner)->get(route('reviews.index'))
            ->assertOk()
            ->assertSee('mine-comment')
            ->assertDontSee('foreign-comment');

        $this->assertNotNull($mine->id);
    }

    public function test_owner_can_delete_a_review_but_not_another_shops(): void
    {
        $mine = Review::factory()->for($this->shop)->create();
        $foreign = Review::factory()->create();

        $this->actingAs($this->owner)->delete(route('reviews.destroy', $foreign))->assertNotFound();
        $this->actingAs($this->owner)->delete(route('reviews.destroy', $mine))->assertRedirect(route('reviews.index'));

        $this->assertModelMissing($mine);
        $this->assertModelExists($foreign);
    }

    public function test_reviews_page_requires_login(): void
    {
        $this->get(route('reviews.index'))->assertRedirect(route('login'));
    }

    public function test_menu_shows_the_rate_us_button(): void
    {
        $this->get('/kahve-evi')->assertOk()->assertSee(__('app.review.footer_cta', [], 'ar'));
    }
}
