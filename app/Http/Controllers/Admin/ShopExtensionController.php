<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\SubscriptionEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Give a shop free days (a courtesy, a support case…) without a payment.
 */
class ShopExtensionController extends Controller
{
    public const DAY_OPTIONS = [7, 14, 30, 60];

    public function store(Request $request, Shop $shop): RedirectResponse
    {
        $days = (int) $request->validate([
            'days' => ['required', 'integer', 'in:'.implode(',', self::DAY_OPTIONS)],
        ])['days'];

        $shop->extendBy($days);

        SubscriptionEvent::create(['shop_id' => $shop->id, 'user_id' => $request->user()->id, 'type' => SubscriptionEvent::EXTENSION, 'amount' => $days]);

        return back()->with('status', __('billing.admin.extended', [
            'days' => $days,
            'date' => $shop->paymentDueAt()->locale(app()->getLocale())->translatedFormat('j F Y'),
        ]));
    }
}
