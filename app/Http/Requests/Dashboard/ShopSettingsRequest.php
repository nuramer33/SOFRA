<?php

namespace App\Http\Requests\Dashboard;

use App\Http\Requests\Concerns\ValidatesTranslations;
use App\Models\Shop;
use App\Support\Analytics;
use App\Support\Locales;
use App\Support\Numbers;
use App\Tenancy\CurrentShop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShopSettingsRequest extends FormRequest
{
    use ValidatesTranslations;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => mb_strtolower(trim((string) $this->input('slug'))),
            'brand_color' => mb_strtolower(trim((string) $this->input('brand_color'))),
            'whatsapp_number' => Numbers::phone($this->input('whatsapp_number')),
            'instagram' => $this->instagramHandle($this->input('instagram')),
            'delivery_fee' => $this->blankToNull($this->input('delivery_fee')),
            'min_order' => $this->blankToNull($this->input('min_order')),
        ]);
    }

    public function rules(): array
    {
        $locales = Locales::codes();

        return [
            ...$this->translationRules('name', $locales, $this->input('default_locale'), 80),
            ...$this->translationRules('tagline', $locales, null, 120),
            ...$this->translationRules('address', $locales, null, 200),
            ...$this->translationRules('hours', $locales, null, 150),
            'maps_url' => ['nullable', 'url:http,https', 'max:500'],
            'instagram' => ['nullable', 'regex:/^[A-Za-z0-9._]{1,30}$/'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'min_order' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'slug' => Shop::slugRules($this->shop()),
            'brand_color' => ['required', 'regex:/^#[0-9a-f]{6}$/'],
            // International format without "+": country code + number, 8–15 digits.
            'whatsapp_number' => ['nullable', 'regex:/^[1-9][0-9]{7,14}$/'],
            'currency' => ['required', Rule::in(config('sofra.currencies'))],
            'order_types' => ['required', 'array', 'min:1'],
            'order_types.*' => [Rule::in(Shop::ORDER_TYPES)],
            'enabled_locales' => ['required', 'array', 'min:1'],
            'enabled_locales.*' => [Rule::in($locales)],
            'default_locale' => ['required', Rule::in($locales), Rule::in((array) $this->input('enabled_locales', []))],
            'template' => ['sometimes', Rule::in(config('sofra.templates'))],
            'analytics_retention_days' => ['sometimes', 'integer', Rule::in(Analytics::RETENTION_OPTIONS)],
            'logo' => $this->imageRules(),
            'remove_logo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'whatsapp_number.regex' => __('app.settings.whatsapp_invalid'),
            'default_locale.in' => __('app.settings.default_locale_invalid'),
            'instagram.regex' => __('shopinfo.settings.instagram_invalid'),
        ];
    }

    public function attributes(): array
    {
        $locales = Locales::codes();

        return [
            ...$this->translationAttributes('name', __('app.fields.shop_name'), $locales),
            ...$this->translationAttributes('tagline', __('app.fields.tagline'), $locales),
            ...$this->translationAttributes('address', __('shopinfo.settings.address'), $locales),
            ...$this->translationAttributes('hours', __('shopinfo.settings.hours'), $locales),
            'maps_url' => __('shopinfo.settings.maps_url'),
            'instagram' => __('shopinfo.settings.instagram'),
            'delivery_fee' => __('shopinfo.settings.delivery_fee'),
            'min_order' => __('shopinfo.settings.min_order'),
            'slug' => __('app.auth.slug'),
            'brand_color' => __('app.fields.brand_color'),
            'whatsapp_number' => __('app.fields.whatsapp'),
            'currency' => __('app.fields.currency'),
            'order_types' => __('app.fields.order_types'),
            'enabled_locales' => __('app.fields.languages'),
            'default_locale' => __('app.fields.default_language'),
            'logo' => __('app.fields.logo'),
            'analytics_retention_days' => __('analytics.retention.label'),
        ];
    }

    /**
     * Accepts @name,
ame or a full instagram.com link and keeps just the handle.
     */
    private function instagramHandle(mixed $value): ?string
    {
        $handle = trim((string) $value);
        $handle = preg_replace('~^(https?://)?(www\.)?instagram\.com/~i', '', $handle);
        $handle = ltrim(explode('?', explode('/', $handle)[0])[0], '@');

        return $handle === '' ? null : $handle;
    }

    private function blankToNull(mixed $value): mixed
    {
        return $value === null || trim((string) $value) === '' ? null : str_replace(',', '.', trim((string) $value));
    }

    public function shop(): Shop
    {
        return app(CurrentShop::class)->get();
    }
}
