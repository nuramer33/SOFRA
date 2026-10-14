<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\SubscriptionEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Record a subscription payment received outside the app (cash, transfer…).
 */
class ShopPaymentController extends Controller
{
    public const MONTH_OPTIONS = [1, 3, 6, 12];

    public function store(Request $request, Shop $shop): RedirectResponse
    {
        $months = (int) $request->validate([
            'months' => ['required', 'integer', 'in:'.implode(',', self::MONTH_OPTIONS)],
        ])['months'];

        $shop->recordPayment($months);

        SubscriptionEvent::create(['shop_id' => $shop->id, 'user_id' => $request->user()->id, 'type' => SubscriptionEvent::PAYMENT, 'amount' => $months]);

        return back()->with('status', __('app.admin.payment_recorded', [
            'date' => $shop->paid_until->locale(app()->getLocale())->translatedFormat('j F Y'),
        ]));
    }
}
