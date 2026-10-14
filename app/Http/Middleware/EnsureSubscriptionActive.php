<?php

namespace App\Http\Middleware;

use App\Tenancy\CurrentShop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Once a shop's subscription has expired, the owner can still sign in, look at
 * everything and renew, but cannot change anything (no data is deleted).
 * Decided on the server from the shop's dates, never from the browser.
 */
class EnsureSubscriptionActive
{
    public function __construct(private CurrentShop $currentShop) {}

    public function handle(Request $request, Closure $next): Response
    {
        $shop = $this->currentShop->get();

        $allowed = $request->isMethodSafe() || $request->routeIs('account.*', 'billing.*'); // an expired owner must still be able to pay

        if ($shop->subscriptionExpired() && ! $allowed) {
            $message = __('billing.blocked');

            return $request->expectsJson()
                ? response()->json(['message' => $message], 403)
                : redirect()->route('billing.show')->with('status', $message);
        }

        return $next($request);
    }
}
