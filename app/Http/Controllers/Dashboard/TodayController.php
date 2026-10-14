<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ProductSuggestion;
use App\Tenancy\CurrentShop;
use Illuminate\View\View;

/**
 * "Today": the few things an owner changes on a phone during the day —
 * pause orders, sold out, prices, photos and offers. Everything is limited
 * to the owner's shop by the tenant scope.
 */
class TodayController extends Controller
{
    public function __invoke(CurrentShop $currentShop): View
    {
        $shop = $currentShop->get();

        $categories = Category::query()
            ->with(['products' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        $products = $categories->flatMap(fn (Category $category) => $category->products);

        return view('dashboard.today', [
            'shop' => $shop,
            'categories' => $categories,
            'items' => $products->mapWithKeys(fn ($product) => [$product->id => ProductQuickController::summary($product, $shop)])->all(),
            'offers' => ProductSuggestion::query()->with(['product', 'suggestedProduct'])->orderBy('id')->get(),
            'locales' => $shop->contentLocales(),
        ]);
    }
}
