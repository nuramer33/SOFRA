<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
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
     * Drag-and-drop: the browser sends the ids in their new order.
     */
    public function reorderCategories(Request $request): JsonResponse
    {
        Category::arrange($this->ids($request));

        return response()->json(['ok' => true]);
    }

    public function reorderProducts(Request $request, Category $category): JsonResponse
    {
        Product::arrange($this->ids($request), Product::query()->where('category_id', $category->id));

        return response()->json(['ok' => true]);
    }

    /**
     * @return list<int>
     */
    private function ids(Request $request): array
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'max:500'],
            'ids.*' => ['integer'],
        ])['ids'];

        return array_map('intval', $ids);
    }

    /**
     * @return 'up'|'down'
     */
    private function direction(Request $request): string
    {
        return $request->validate(['direction' => ['required', 'in:up,down']])['direction'];
    }
}
