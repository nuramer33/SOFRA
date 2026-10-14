<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Move up / move down" for categories and products. Redirects back to the
 * moved row (#anchor) so the owner doesn't lose their place in the list.
 */
class PositionController extends Controller
{
    public function category(Request $request, Category $category): RedirectResponse
    {
        $category->move($this->direction($request));

        return redirect()->to(route('categories.index').'#category-'.$category->id);
    }

    public function product(Request $request, Product $product): RedirectResponse
    {
        $product->move($this->direction($request));

        return redirect()->to(route('products.index').'#product-'.$product->id);
    }

    /**
     * @return 'up'|'down'
     */
    private function direction(Request $request): string
    {
        return $request->validate(['direction' => ['required', 'in:up,down']])['direction'];
    }
}
