<?php

namespace App\Http\Requests\Dashboard;

use App\Http\Requests\Concerns\ValidatesTranslations;
use App\Models\Product;
use App\Models\Shop;
use App\Support\Numbers;
use App\Tenancy\CurrentShop;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Partial product edit from the Today page: only the fields that are sent
 * are validated and changed (name, description, price, sale price, photo).
 */
class QuickProductRequest extends FormRequest
{
    use ValidatesTranslations;

    public function authorize(): bool
    {
        return true; // {product} is already limited to the owner's shop by the tenant scope
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        foreach (['price', 'compare_at_price'] as $field) {
            if ($this->has($field)) {
                $merge[$field] = Numbers::decimal($this->input($field));
            }
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        $shop = $this->shop();
        $rules = [
            'price' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999'],
            'compare_at_price' => ['sometimes', 'nullable', 'numeric', 'decimal:0,2', 'max:9999999'],
            'image' => $this->imageRules(),
            'remove_image' => ['boolean'],
        ];

        if ($this->has('name')) {
            $rules += $this->translationRules('name', $shop->contentLocales(), $shop->default_locale, 80);
        }
        if ($this->has('description')) {
            $rules += $this->translationRules('description', $shop->contentLocales(), null, 400);
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $compare = $this->input('compare_at_price');
            $price = $this->has('price') ? $this->input('price') : $this->product()->price;

            if (filled($compare) && is_numeric($compare) && is_numeric($price) && (float) $compare <= (float) $price) {
                $validator->errors()->add('compare_at_price', __('quick.edit.compare_error'));
            }
        });
    }

    public function attributes(): array
    {
        $locales = $this->shop()->contentLocales();

        return [
            ...$this->translationAttributes('name', __('app.fields.name'), $locales),
            ...$this->translationAttributes('description', __('app.fields.description'), $locales),
            'price' => __('app.fields.price'),
            'compare_at_price' => __('app.fields.compare_at_price'),
            'image' => __('app.fields.image'),
        ];
    }

    public function product(): Product
    {
        return $this->route('product');
    }

    public function shop(): Shop
    {
        return app(CurrentShop::class)->get();
    }
}
