<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaceOrderRequest;
use App\Models\Product;
use App\Models\ProductSuggestion;
use App\Models\Shop;
use App\Support\Analytics;
use App\Support\WhatsAppOrder;
use App\Tenancy\CurrentShop;
use Illuminate\Http\JsonResponse;
use Spatie\Translatable\Facades\Translatable;

/**
 * Turns the cart into a WhatsApp message. Nothing is stored: the order
 * lives in the chat between the customer and the shop.
 */
class OrderController extends Controller
{
    public function store(PlaceOrderRequest $request, Shop $shop, CurrentShop $currentShop): JsonResponse
    {
        // canReceiveOrders() is also false while an expired subscription pauses ordering.
        abort_unless($shop->isPubliclyVisible() && $shop->canReceiveOrders(), 404);

        // Paused by the owner or outside ordering hours: a clear message, not an error page.
        if ($reason = $shop->orderingClosedReason()) {
            return response()->json([
                'message' => __("quick.closed.{$reason}"),
                'closed' => $reason,
            ], 422);
        }

        $currentShop->set($shop); // product lookups below are limited to this shop
        Translatable::fallback(fallbackLocale: $shop->default_locale, fallbackAny: true);

        $quantities = $request->quantities();

        $products = Product::query()
            ->whereKey(array_keys($quantities))
            ->where('is_available', true)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->get()
            ->keyBy('id');

        // Sold out since the page was opened, hidden, or not this shop's at all.
        $unavailable = array_values(array_diff(array_keys($quantities), $products->keys()->all()));
        if ($unavailable !== []) {
            return response()->json([
                'message' => __('app.cart.unavailable'),
                'unavailable' => $unavailable,
            ], 422);
        }

        // Offers only count when both products are in this (already validated) order.
        $suggestions = ProductSuggestion::query()
            ->where('is_active', true)
            ->whereIn('product_id', array_keys($quantities))
            ->whereIn('suggested_product_id', array_keys($quantities))
            ->get();

        $order = WhatsAppOrder::make($shop, $products, $quantities, $request->safe()->only([
            'type', 'table', 'name', 'address', 'note',
        ]), $suggestions);

        // A delivery minimum is checked on the server too: the browser hint is only a courtesy.
        if ($order->details['type'] === 'delivery' && $shop->min_order !== null && $order->subtotal() < (float) $shop->min_order) {
            return response()->json([
                'message' => __('shopinfo.cart.min_order', ['amount' => $shop->formatPrice($shop->min_order)]),
                'errors' => ['type' => [__('shopinfo.cart.min_order', ['amount' => $shop->formatPrice($shop->min_order)])]],
            ], 422);
        }

        // Counted as a button click only: whether the customer then sends the
        // chat message is unknown to us.
        Analytics::record($shop, $request, Analytics::WHATSAPP_CLICK);

        // The public demo shop has a made-up number: instead of opening
        // WhatsApp to nobody, the menu shows the message it would send.
        if ($shop->slug === config('sofra.demo_slug')) {
            return response()->json([
                'reference' => $order->reference,
                'demo' => true,
                'message' => str_replace('*', '', $order->message()),
            ]);
        }

        return response()->json([
            'reference' => $order->reference,
            'url' => $order->url(),
        ]);
    }
}
