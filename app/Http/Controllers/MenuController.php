<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSuggestion;
use App\Models\Shop;
use App\Support\Analytics;
use App\Support\Locales;
use App\Support\Money;
use App\Tenancy\CurrentShop;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Spatie\Translatable\Facades\Translatable;

/**
 * The public, customer-facing menu at /{slug}.
 */
class MenuController extends Controller
{
    public function show(Request $request, Shop $shop, CurrentShop $currentShop): View
    {
        abort_unless($shop->isPubliclyVisible(), 404);

        // From here on, every Category/Product query is limited to this shop.
        $currentShop->set($shop);

        app()->setLocale($this->menuLocale($request, $shop));

        // Missing translation? Show the shop's main language, not a blank.
        Translatable::fallback(fallbackLocale: $shop->default_locale, fallbackAny: true);

        $categories = Category::query()
            ->where('is_active', true)
            ->whereHas('products')
            ->with(['products' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        $products = $categories->flatMap(fn (Category $category) => $category->products);

        Analytics::record($shop, $request, Analytics::MENU_VIEW);

        return view($this->template($shop), [
            'shop' => $shop,
            'categories' => $categories,
            'productCount' => $products->count(),
            'featured' => $products->where('is_featured', true)->where('is_available', true)->values(),
            'sheetData' => $this->sheetData($shop, $products),
            'menuConfig' => $this->menuConfig($shop),
        ]);
    }

    /**
     * Settings the menu's JavaScript needs (cart, money formatting).
     *
     * @return array<string, mixed>
     */
    private function menuConfig(Shop $shop): array
    {
        $symbol = Money::symbol($shop->currency);

        return [
            'shopId' => $shop->id,
            'lang' => app()->getLocale(),
            'orderUrl' => route('menu.order', $shop),
            'trackUrl' => route('menu.track', $shop),
            'orderTypes' => $shop->orderTypes(),
            'currency' => [
                'symbol' => $symbol,
                // Same rule as Money::format(): "$12.50" only in English.
                'prefix' => app()->getLocale() === 'en' && mb_strlen($symbol) === 1,
            ],
            'deliveryFee' => (float) $shop->deliveryFeeFor('delivery'),
            'minOrder' => $shop->min_order !== null ? (float) $shop->min_order : null,
            't' => [
                'percentOff' => __('suggestions.menu.percent_off', ['value' => ':value']),
                'amountOff' => __('suggestions.menu.amount_off', ['amount' => ':amount']),
                'withThis' => __('suggestions.menu.with_this', ['name' => ':name']),
                'error' => __('app.cart.error'),
                'minOrder' => $shop->min_order !== null ? __('shopinfo.cart.min_order', ['amount' => $shop->formatPrice($shop->min_order)]) : null,
            ],
        ];
    }

    /**
     * Language order: ?lang= (shareable links) → the visitor's earlier choice
     * on this shop → the shop's main language. Only the shop's languages count.
     */
    private function menuLocale(Request $request, Shop $shop): string
    {
        $available = $shop->contentLocales();
        $sessionKey = "menu_locale.{$shop->id}";

        $requested = $request->query('lang');
        if (is_string($requested) && in_array($requested, $available, true)) {
            $request->session()->put($sessionKey, $requested);

            return $requested;
        }

        $remembered = $request->session()->get($sessionKey);

        if (in_array($remembered, $available, true)) {
            return $remembered;
        }

        // First visit: speak the customer's phone language when the shop offers it.
        return Locales::fromBrowser($request, $available) ?? $shop->default_locale;
    }

    private function template(Shop $shop): string
    {
        $view = "menu.templates.{$shop->template}";

        return view()->exists($view) ? $view : 'menu.templates.classic';
    }

    /**
     * "Goes well with" entries for one product. Only products that can be
     * ordered right now are offered, and a discount is only passed on when it
     * is valid for the current price (so the menu never advertises a fake one).
     *
     * @param  Collection<int, ProductSuggestion>  $suggestions
     * @param  Collection<int, Product>  $byId
     * @return list<array<string, mixed>>
     */
    private function suggestionsFor(Product $product, Collection $suggestions, Collection $byId): array
    {
        if (! $product->is_available) {
            return [];
        }

        return $suggestions
            ->filter(fn (ProductSuggestion $suggestion) => $byId->get($suggestion->suggested_product_id)?->is_available)
            ->map(function (ProductSuggestion $suggestion) use ($byId) {
                $price = (float) $byId->get($suggestion->suggested_product_id)->price;

                return [
                    'item' => $suggestion->suggested_product_id,
                    'title' => $suggestion->title ?: null,
                    'text' => $suggestion->description ?: null,
                    'discount' => $suggestion->hasValidDiscount($price)
                        ? ['type' => $suggestion->discount_type, 'value' => (float) $suggestion->discount_value]
                        : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Everything the product sheet needs, keyed by product id, already
     * translated and formatted — so opening a product needs no request.
     *
     * @param  Collection<int, Product>  $products
     * @return array<int, array<string, mixed>>
     */
    private function sheetData(Shop $shop, Collection $products): array
    {
        $suggestions = ProductSuggestion::query()->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')->get()->groupBy('product_id');

        return $products->mapWithKeys(fn (Product $product) => [$product->id => [
            'suggest' => $this->suggestionsFor($product, $suggestions->get($product->id, collect()), $products->keyBy('id')),
            'name' => $product->name,
            'description' => $product->description ?: null,
            'price' => $shop->formatPrice($product->price),
            'amount' => (float) $product->price,
            'thumb' => $product->imageUrl('sm'),
            'compare' => $product->isOnSale() ? $shop->formatPrice($product->compare_at_price) : null,
            'image' => $product->imageUrl(),
            'available' => $product->is_available,
        ]])->all();
    }
}
