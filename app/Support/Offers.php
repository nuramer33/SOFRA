<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductSuggestion;
use Illuminate\Support\Collection;

/**
 * The one place that decides what an offer is worth. Prices come from the
 * database, never from the browser (resources/js/menu.js only mirrors this
 * for display; the WhatsApp message uses these numbers).
 *
 * Rule: a suggestion reduces the suggested product's price, one unit per unit
 * of the main product in the same order. If several offers cover the same
 * product, the biggest saving is used first, so nothing is counted twice.
 */
class Offers
{
    /**
     * @param  array<int, int>  $quantities  product id => quantity in the order
     * @param  Collection<int, Product>  $products  keyed by id (the products in the order)
     * @param  Collection<int, ProductSuggestion>  $suggestions
     * @return array<int, float> suggested product id => total discount for that line
     */
    public static function discounts(array $quantities, Collection $products, Collection $suggestions): array
    {
        $discounts = [];

        foreach ($suggestions->where('is_active', true)->groupBy('suggested_product_id') as $itemId => $rules) {
            $item = $products->get($itemId);
            if ($item === null || ($quantities[$itemId] ?? 0) < 1) {
                continue;
            }

            $price = (float) $item->price;
            $candidates = $rules
                ->filter(fn (ProductSuggestion $rule) => ($quantities[$rule->product_id] ?? 0) > 0 && $rule->product_id !== $rule->suggested_product_id)
                ->map(fn (ProductSuggestion $rule) => ['perUnit' => $rule->discountPerUnit($price), 'units' => $quantities[$rule->product_id]])
                ->filter(fn (array $candidate) => $candidate['perUnit'] > 0)
                ->sortByDesc('perUnit');

            $remaining = $quantities[$itemId];
            $total = 0.0;
            foreach ($candidates as $candidate) {
                $units = min($remaining, $candidate['units']);
                $total += $units * $candidate['perUnit'];
                $remaining -= $units;
            }

            if ($total > 0) {
                $discounts[(int) $itemId] = round(min($total, $price * $quantities[$itemId]), 2);
            }
        }

        return $discounts;
    }
}
