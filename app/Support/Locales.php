<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Single source of truth for languages and text direction.
 */
class Locales
{
    /** @return array<string, array{name: string, dir: string}> */
    public static function all(): array
    {
        return config('sofra.locales');
    }

    /** @return list<string> e.g. ['ar', 'tr', 'en'] */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function default(): string
    {
        return self::codes()[0];
    }

    public static function supports(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, self::all());
    }

    /**
     * The first language in the visitor's Accept-Language list that we offer
     * ("tr-TR" matches "tr"). Null when none matches or no header was sent.
     *
     * @param  list<string>|null  $offered  defaults to every supported language
     */
    public static function fromBrowser(Request $request, ?array $offered = null): ?string
    {
        $offered ??= self::codes();

        foreach ($request->getLanguages() as $language) {
            $code = strtolower(substr($language, 0, 2));

            if (in_array($code, $offered, true)) {
                return $code;
            }
        }

        return null;
    }

    public static function dir(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return self::all()[$locale]['dir'] ?? 'ltr';
    }

    public static function isRtl(?string $locale = null): bool
    {
        return self::dir($locale) === 'rtl';
    }

    public static function name(string $locale): string
    {
        return self::all()[$locale]['name'] ?? $locale;
    }
}
