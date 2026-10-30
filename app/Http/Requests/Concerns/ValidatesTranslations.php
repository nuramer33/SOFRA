<?php

namespace App\Http\Requests\Concerns;

use App\Support\AutoTranslator;
use App\Support\Locales;

/**
 * Helpers for fields written in several languages, submitted as
 * name[ar], name[tr], name[en].
 */
trait ValidatesTranslations
{
    /**
     * Only the main language is required; the others are optional and fall
     * back to it on the menu.
     *
     * @param  list<string>  $locales
     * @return array<string, array<int, string>>
     */
    protected function translationRules(string $field, array $locales, ?string $requiredLocale, int $max): array
    {
        $rules = [$field => ['nullable', 'array']];

        foreach ($locales as $locale) {
            $rules["{$field}.{$locale}"] = [
                $locale === $requiredLocale ? 'required' : 'nullable',
                'string',
                "max:{$max}",
            ];
        }

        return $rules;
    }

    /**
     * Error messages say e.g. "الاسم (Türkçe)" so the owner knows which tab.
     *
     * @param  list<string>  $locales
     * @return array<string, string>
     */
    protected function translationAttributes(string $field, string $label, array $locales): array
    {
        $attributes = [];

        foreach ($locales as $locale) {
            $attributes["{$field}.{$locale}"] = $label.' ('.Locales::name($locale).')';
        }

        return $attributes;
    }

    /**
     * Trimmed, non-empty translations for the given locales only.
     *
     * @param  list<string>  $locales
     * @return array<string, string>
     */
    public function translations(string $field, array $locales): array
    {
        $values = (array) $this->validated($field, []);

        $texts = collect($locales)
            ->mapWithKeys(fn (string $locale) => [$locale => trim((string) ($values[$locale] ?? ''))])
            ->filter(fn (string $value) => $value !== '')
            ->all();

        return app(AutoTranslator::class)->fill($texts, $locales);
    }

    /**
     * @return array<int, string>
     */
    protected function imageRules(): array
    {
        $max = config('sofra.images.max_dimension');

        return [
            'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:'.config('sofra.images.max_upload_kb'),
            "dimensions:max_width={$max},max_height={$max}",
        ];
    }
}
