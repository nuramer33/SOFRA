<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\SubscriptionEvent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Support tool for owners who forgot their password while e-mail is not set
 * up: generates a temporary one, shown to the administrator exactly once.
 */
class ShopUserPasswordController extends Controller
{
    public function update(Request $request, Shop $shop, User $user): RedirectResponse
    {
        abort_unless($user->shop_id === $shop->id && ! $user->isAdmin(), 404);

        $password = Str::password(12, symbols: false);

        $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

        SubscriptionEvent::create(['shop_id' => $shop->id, 'user_id' => $request->user()->id, 'type' => SubscriptionEvent::PASSWORD_RESET, 'amount' => null]);

        return back()->with([
            'status' => __('billing.admin.password_reset', ['email' => $user->email]),
            'temporary_password' => $password,
        ]);
    }
}
