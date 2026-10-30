<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Lets an owner write each menu text once, in their main language, and fills
 * the other languages automatically.
 *
 * Uses Google's free web translation endpoint (no key, no card). It is
 * best-effort: if it is unreachable the other languages are simply left
 * empty and the menu shows the main language instead. Every result is cached,
 * so saving an unchanged text never calls the service again.
 */
class AutoTranslator
{
    private const ENDPOINT = 'https://translate.googleapis.com/translate_a/single';

    /**
     * Adds a translation for each of $locales that has no text yet.
     * Texts the owner wrote themselves are never overwritten.
     *
     * @param  array<string, string>  $texts  locale => text, non-empty only
     * @param  list<string>  $locales  main language first
     * @return array<string, string>
     */
    public function fill(array $texts, array $locales): array
    {
        $source = collect($locales)->first(fn (string $locale) => isset($texts[$locale]));

        if ($source === null || ! config('sofra.auto_translate')) {
            return $texts;
        }

        foreach ($locales as $locale) {
            if (! isset($texts[$locale]) && ($translated = $this->translate($texts[$source], $source, $locale)) !== null) {
                $texts[$locale] = $translated;
            }
        }

        return $texts;
    }

    public function translate(string $text, string $from, string $to): ?string
    {
        return Cache::rememberForever('translate:'.md5("{$from}|{$to}|{$text}"), function () use ($text, $from, $to) {
            try {
                $response = Http::timeout(5)->get(self::ENDPOINT, [
                    'client' => 'gtx', 'sl' => $from, 'tl' => $to, 'dt' => 't', 'q' => $text,
                ]);
            } catch (Throwable) {
                return null;
            }

            $parts = $response->successful() ? $response->json('0') : null;
            $translated = is_array($parts) ? trim(collect($parts)->pluck(0)->implode('')) : '';

            return $translated !== '' ? $translated : null;
        });
    }
}
