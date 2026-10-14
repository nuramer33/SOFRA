<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Product;
use App\Models\ProductSuggestion;
use App\Models\Shop;
use App\Support\Locales;
use App\Support\Numbers;
use App\Tenancy\CurrentShop;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The "goes well with" list of one product, saved all at once.
 */
class ProductSuggestionsRequest extends FormRequest
{
    private const MAX_SUGGESTIONS = 8;

    public function authorize(): bool
    {
        // The {product} in the URL is already limited to the owner's shop by the tenant scope.
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rows = collect((array) $this->input('suggestions', []))
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row) {
                $row['discount_value'] = Numbers::decimal($row['discount_value'] ?? null);
                if (blank($row['discount_type'] ?? null)) {
                    $row['discount_type'] = null;
                    $row['discount_value'] = null;
                }

                return $row;
            })
            ->values()
            ->all();

        $this->merge(['suggestions' => $rows]);
    }

    public function rules(): array
    {
        $shop = $this->shop();

        $rules = [
            'suggestions' => ['nullable', 'array', 'max:'.self::MAX_SUGGESTIONS],
            'suggestions.*.suggested_product_id' => [
                'required', 'integer', 'distinct',
                Rule::exists('products', 'id')->where('shop_id', $shop->id),
                Rule::notIn([$this->product()->id]),
            ],
            'suggestions.*.discount_type' => ['nullable', Rule::in(ProductSuggestion::DISCOUNT_TYPES)],
            'suggestions.*.discount_value' => ['nullable', 'required_with:suggestions.*.discount_type', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999'],
            'suggestions.*.is_active' => ['nullable', 'boolean'],
        ];

        foreach ($shop->contentLocales() as $locale) {
            $rules["suggestions.*.title.{$locale}"] = ['nullable', 'string', 'max:80'];
            $rules["suggestions.*.description.{$locale}"] = ['nullable', 'string', 'max:200'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $shop = $this->shop();
            $required = $shop->default_locale;
            $prices = Product::query()->pluck('price', 'id');

            foreach ((array) $this->input('suggestions', []) as $index => $row) {
                foreach (['title', 'description'] as $field) {
                    $texts = array_filter(array_map('trim', array_map('strval', (array) ($row[$field] ?? []))));

                    if ($texts !== [] && blank($texts[$required] ?? null)) {
                        $validator->errors()->add("suggestions.{$index}.{$field}.{$required}", __('suggestions.errors.missing_default', [
                            'row' => $index + 1,
                            'field' => __("suggestions.fields.{$field}"),
                            'language' => Locales::name($required),
                        ]));
                    }
                }

                $type = $row['discount_type'] ?? null;
                $value = (float) ($row['discount_value'] ?? 0);
                $price = (float) ($prices[$row['suggested_product_id'] ?? 0] ?? 0);

                if ($type === ProductSuggestion::DISCOUNT_PERCENT && $value > ProductSuggestion::MAX_PERCENT) {
                    $validator->errors()->add("suggestions.{$index}.discount_value", __('suggestions.errors.percent_max', ['row' => $index + 1, 'max' => ProductSuggestion::MAX_PERCENT]));
                }

                if ($type === ProductSuggestion::DISCOUNT_FIXED && $price > 0 && $value >= $price) {
                    $validator->errors()->add("suggestions.{$index}.discount_value", __('suggestions.errors.fixed_max', ['row' => $index + 1]));
                }
            }
        });
    }

    public function attributes(): array
    {
        $attributes = [];

        foreach (array_keys((array) $this->input('suggestions', [])) as $index) {
            $row = $index + 1;
            $attributes["suggestions.{$index}.suggested_product_id"] = __('suggestions.row_field', ['row' => $row, 'field' => __('suggestions.fields.product')]);
            $attributes["suggestions.{$index}.discount_type"] = __('suggestions.row_field', ['row' => $row, 'field' => __('suggestions.fields.discount')]);
            $attributes["suggestions.{$index}.discount_value"] = __('suggestions.row_field', ['row' => $row, 'field' => __('suggestions.fields.discount_value')]);

            foreach ($this->shop()->contentLocales() as $locale) {
                $language = Locales::name($locale);
                $attributes["suggestions.{$index}.title.{$locale}"] = __('suggestions.row_field', ['row' => $row, 'field' => __('suggestions.fields.title')." ({$language})"]);
                $attributes["suggestions.{$index}.description.{$locale}"] = __('suggestions.row_field', ['row' => $row, 'field' => __('suggestions.fields.description')." ({$language})"]);
            }
        }

        return $attributes;
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
