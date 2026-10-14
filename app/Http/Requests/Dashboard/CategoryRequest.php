<?php

namespace App\Http\Requests\Dashboard;

use App\Http\Requests\Concerns\ValidatesTranslations;
use App\Models\Shop;
use App\Tenancy\CurrentShop;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Used for both creating and updating a category.
 */
class CategoryRequest extends FormRequest
{
    use ValidatesTranslations;

    public function authorize(): bool
    {
        // Ownership is enforced by the tenant scope: another shop's category
        // can't even be loaded (404) before this request runs.
        return true;
    }

    public function rules(): array
    {
        $shop = $this->shop();

        return [
            ...$this->translationRules('name', $shop->contentLocales(), $shop->default_locale, 60),
            'is_active' => ['boolean'],
            'image' => $this->imageRules(),
            'remove_image' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            ...$this->translationAttributes('name', __('app.fields.name'), $this->shop()->contentLocales()),
            'image' => __('app.fields.image'),
        ];
    }

    public function shop(): Shop
    {
        return app(CurrentShop::class)->get();
    }
}
