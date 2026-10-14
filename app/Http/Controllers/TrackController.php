<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use App\Support\Analytics;
use App\Tenancy\CurrentShop;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * Counts product views and cart additions from the public menu.
 * Stores only a counter (see App\Support\Analytics), never who or what.
 */
class TrackController extends Controller
{
    public function store(Request $request, Shop $shop, CurrentShop $currentShop): Response
    {
        abort_unless($shop->isPubliclyVisible(), 404);

        $data = $request->validate([
            'event' => ['required', Rule::in([Analytics::PRODUCT_VIEW, Analytics::ADD_TO_CART])],
            'product' => ['required', 'integer', 'min:1'],
        ]);

        $currentShop->set($shop); // the lookup below can only find this shop's products

        $product = Product::query()
            ->whereKey($data['product'])
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->first();

        // Hidden, unknown or another shop's product: say nothing, count nothing.
        $countable = $product !== null
            && ($data['event'] === Analytics::PRODUCT_VIEW || $product->is_available);

        if ($countable) {
            Analytics::record($shop, $request, $data['event'], $product->id);
        }

        return response()->noContent();
    }
}
