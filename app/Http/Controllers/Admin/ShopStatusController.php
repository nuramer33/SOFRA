<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\SubscriptionEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Suspend / re-activate a shop. A suspended shop's public menu returns 404;
 * the owner can still log in and edit (nothing is deleted).
 */
class ShopStatusController extends Controller
{
    public function update(Request $request, Shop $shop): RedirectResponse
    {
        $shop->forceFill(['is_active' => ! $shop->is_active])->save();

        SubscriptionEvent::create([
            'shop_id' => $shop->id,
            'user_id' => $request->user()->id,
            'type' => $shop->is_active ? SubscriptionEvent::ACTIVATION : SubscriptionEvent::SUSPENSION,
        ]);

        return back()->with('status', __($shop->is_active ? 'app.admin.activated' : 'app.admin.suspended_done'));
    }
}
