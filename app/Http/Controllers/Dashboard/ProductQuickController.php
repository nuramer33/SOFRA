<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\QuickProductRequest;
use App\Models\Product;
use App\Support\ImageStore;
use Illuminate\Http\JsonResponse;

/**
 * Edit a product's name, description, price, sale price or photo without
 * leaving the Today page. Answers JSON so the page can update in place.
 */
class ProductQuickController extends Controller
{
    public function update(QuickProductRequest $request, Product $product, ImageStore $images): JsonResponse
    {
        $shop = $request->shop();
        $locales = $shop->contentLocales();

        if ($request->has('name')) {
            $product->replaceTranslations('name', $request->translations('name', $locales));
        }
        if ($request->has('description')) {
            $product->replaceTranslations('description', $request->translations('description', $locales));
        }
        if ($request->has('price')) {
            $product->price = $request->validated('price');
        }
        if ($request->has('compare_at_price')) {
            $product->compare_at_price = $request->validated('compare_at_price');
        }

        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            $images->delete($product->image_path);
            $product->image_path = $request->hasFile('image')
                ? $images->store($request->file('image'), $shop, 'product')
                : null;
        }

        $product->save();

        return response()->json([
            'message' => __('quick.edit.saved'),
            'product' => self::summary($product->refresh(), $shop),
        ]);
    }

    /**
     * What the Today page shows for a product.
     *
     * @return array<string, mixed>
     */
    public static function summary(Product $product, $shop): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'names' => $product->getTranslations('name'),
            'descriptions' => $product->getTranslations('description'),
            'price' => (float) $product->price,
            'price_label' => $shop->formatPrice($product->price),
            'compare' => $product->compare_at_price !== null ? (float) $product->compare_at_price : null,
            'compare_label' => $product->isOnSale() ? $shop->formatPrice($product->compare_at_price) : null,
            'on_sale' => $product->isOnSale(),
            'thumb' => $product->imageUrl('sm'),
            'available' => $product->is_available,
        ];
    }
}
