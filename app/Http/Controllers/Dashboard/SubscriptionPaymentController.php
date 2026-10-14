<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\Stripe;
use App\Tenancy\CurrentShop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Sends the owner to Stripe's own pages: Checkout to start the monthly
 * subscription, and the Billing Portal to change the card or cancel.
 * Nothing is granted here; only the verified webhook adds paid time.
 */
class SubscriptionPaymentController extends Controller
{
    public function checkout(Request $request, CurrentShop $currentShop, Stripe $stripe): RedirectResponse
    {
        abort_unless(Stripe::enabled(), 404);

        $shop = $currentShop->get();

        // Already renewing by itself: manage it instead of paying twice.
        if ($shop->hasAutoRenew()) {
            return redirect()->route('billing.show')->with('status', __('billing.pay.already'));
        }

        try {
            $session = $stripe->checkoutSession($shop, $request->user()->email);
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('billing.show')->with('status', __('billing.pay.failed'));
        }

        return redirect()->away($session['url']);
    }

    public function portal(CurrentShop $currentShop, Stripe $stripe): RedirectResponse
    {
        abort_unless(Stripe::enabled(), 404);

        $shop = $currentShop->get();
        abort_if(blank($shop->stripe_customer_id), 404);

        try {
            $session = $stripe->portalSession($shop->stripe_customer_id);
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('billing.show')->with('status', __('billing.pay.failed'));
        }

        return redirect()->away($session['url']);
    }
}
