<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Shop;
use App\Models\SubscriptionEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ShopUserPasswordControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_sets_a_temporary_password_shown_once(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $owner = User::factory()->create(['shop_id' => Shop::factory()->create()->id]);
        $oldHash = $owner->password;

        $response = $this->post(route('admin.shops.users.password', [$owner->shop_id, $owner]));

        $response->assertRedirect()->assertSessionHas('temporary_password');
        $this->assertTrue(Hash::check(session('temporary_password'), $owner->refresh()->password));
        $this->assertNotSame($oldHash, $owner->password);
        $this->assertDatabaseHas('subscription_events', ['shop_id' => $owner->shop_id, 'type' => SubscriptionEvent::PASSWORD_RESET]);
    }

    public function test_user_of_another_shop_is_not_found(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $stranger = User::factory()->create(['shop_id' => Shop::factory()->create()->id]);
        $shop = Shop::factory()->create();

        $this->post(route('admin.shops.users.password', [$shop, $stranger]))->assertNotFound();
    }

    public function test_shop_owner_cannot_reset_passwords(): void
    {
        $owner = $this->signInOwner();

        $this->post(route('admin.shops.users.password', [$owner->shop, $owner]))->assertNotFound();
    }
}
