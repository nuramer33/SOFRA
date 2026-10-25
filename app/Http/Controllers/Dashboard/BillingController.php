<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Support\Stripe;
use App\Tenancy\CurrentShop;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The owner's subscription page: where they stand, what happens next and how
 * to renew (payments are arranged by hand with the Sofra team for now).
 */
class BillingController extends Controller
{
    public function show(Request $request, CurrentShop $currentShop): View
    {
        $shop = $currentShop->get();

        return view('dashboard.billing', [
            'shop' => $shop,
            'stripeEnabled' => Stripe::enabled(),
            // Only used to pick a message; a "success" in the URL never grants anything.
            'checkout' => in_array($request->query('checkout'), ['success', 'cancelled'], true) ? $request->query('checkout') : null,
            'status' => $shop->billingStatus(),
            'dueAt' => $shop->paymentDueAt(),
            'graceEndsAt' => $shop->paymentDueAt()?->copy()->addDays(config('sofra.billing.grace_days')),
            'expiredMode' => Shop::expiredMenuMode(),
            'price' => config('sofra.billing.monthly_price'),
            'yearlyPrice' => config('sofra.billing.yearly_price'),
            'contact' => $this->contactLink($shop),
        ]);
    }

    private function contactLink(Shop $shop): ?string
    {
        if ($whatsapp = config('sofra.billing.support_whatsapp')) {
            $text = __('billing.contact_message', ['shop' => $shop->name, 'slug' => $shop->slug]);

            return 'https://wa.me/'.$whatsapp.'?text='.rawurlencode($text);
        }

        $email = config('sofra.billing.support_email');

        return $email ? 'mailto:'.$email : null;
    }
}
