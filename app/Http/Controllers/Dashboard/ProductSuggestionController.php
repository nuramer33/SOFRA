<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ProductSuggestionsRequest;
use App\Models\Product;
use App\Models\ProductSuggestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Owner-managed "goes well with" list for one product. The tenant scope
 * limits both the {product} in the URL and every suggested product.
 */
class ProductSuggestionController extends Controller
{
    public function edit(Product $product): View
    {
        return view('dashboard.products.suggestions', [
            'product' => $product,
            'rows' => $product->suggestions()->get(),
            'options' => Product::query()->whereKeyNot($product->id)
                ->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function update(ProductSuggestionsRequest $request, Product $product): RedirectResponse
    {
        $locales = $request->shop()->contentLocales();

        DB::transaction(function () use ($request, $product, $locales) {
            $product->suggestions()->delete();

            foreach ($request->validated('suggestions', []) as $position => $row) {
                $suggestion = new ProductSuggestion([
                    'product_id' => $product->id,
                    'suggested_product_id' => $row['suggested_product_id'],
                    'discount_type' => $row['discount_type'] ?? null,
                    'discount_value' => $row['discount_value'] ?? null,
                    'is_active' => (bool) ($row['is_active'] ?? false),
                    'sort_order' => $position,
                ]);

                foreach (['title', 'description'] as $field) {
                    $suggestion->setTranslations($field, $this->translations((array) ($row[$field] ?? []), $locales));
                }

                $suggestion->save();
            }
        });

        return redirect()->route('products.suggestions.edit', $product)
            ->with('status', __('suggestions.saved'));
    }

    /**
     * Trimmed, non-empty texts for the shop's languages only.
     *
     * @param  array<string, mixed>  $texts
     * @param  list<string>  $locales
     * @return array<string, string>
     */
    private function translations(array $texts, array $locales): array
    {
        return collect($locales)
            ->mapWithKeys(fn (string $locale) => [$locale => trim((string) ($texts[$locale] ?? ''))])
            ->filter(fn (string $text) => $text !== '')
            ->all();
    }
}
