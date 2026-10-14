<?php

namespace App\Http\Requests\Dashboard;

use App\Http\Requests\Concerns\ValidatesTranslations;
use App\Models\Shop;
use App\Support\Numbers;
use App\Tenancy\CurrentShop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Used for both creating and updating a product.
 */
class ProductRequest extends FormRequest
{
    use ValidatesTranslations;

    public function authorize(): bool
    {
        // See CategoryRequest: ownership comes from the tenant scope.
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'price' => Numbers::decimal($this->input('price')),
            'compare_at_price' => Numbers::decimal($this->input('compare_at_price')),
        ]);
    }

    public function rules(): array
    {
        $shop = $this->shop();

        return [
            // The category must be one of THIS shop's categories.
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('shop_id', $shop->id)],
            ...$this->translationRules('name', $shop->contentLocales(), $shop->default_locale, 80),
            ...$this->translationRules('description', $shop->contentLocales(), null, 400),
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999'],
            'compare_at_price' => ['nullable', 'numeric', 'decimal:0,2', 'gt:price', 'max:9999999'],
            'is_available' => ['boolean'],
            'is_featured' => ['boolean'],
            'image' => $this->imageRules(),
            'remove_image' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        $locales = $this->shop()->contentLocales();

        return [
            ...$this->translationAttributes('name', __('app.fields.name'), $locales),
            ...$this->translationAttributes('description', __('app.fields.description'), $locales),
            'category_id' => __('app.fields.category'),
            'price' => __('app.fields.price'),
            'compare_at_price' => __('app.fields.compare_at_price'),
            'image' => __('app.fields.image'),
        ];
    }

    public function shop(): Shop
    {
        return app(CurrentShop::class)->get();
    }
}
