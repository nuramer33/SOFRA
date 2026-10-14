<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Quick "sold out today" switch on the product list.
 * Answers JSON to the instant toggle, and redirects when JS is off.
 */
class ProductAvailabilityController extends Controller
{
    public function update(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $validated = $request->validate(['is_available' => ['required', 'boolean']]);

        $product->update(['is_available' => (bool) $validated['is_available']]);

        return $request->expectsJson()
            ? response()->json(['is_available' => $product->is_available])
            : redirect()->to(route('products.index').'#product-'.$product->id);
    }
}
