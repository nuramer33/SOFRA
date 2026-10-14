<?php

namespace Tests\Feature;

use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every language file must exist in Arabic, Turkish and English with the same
 * keys, so no screen can show a raw key like "quick.hours.title" to a customer.
 */
class LanguageParityTest extends TestCase
{
    private const LOCALES = ['ar', 'tr', 'en'];

    /** @return array<string, array{0: string}> */
    public static function files(): array
    {
        $names = collect(self::LOCALES)
            ->flatMap(fn (string $locale) => glob(dirname(__DIR__, 2)."/lang/{$locale}/*.php"))
            ->map(fn (string $path) => basename($path, '.php'))
            ->unique()
            ->sort();

        return $names->mapWithKeys(fn (string $name) => [$name => [$name]])->all();
    }

    #[DataProvider('files')]
    public function test_a_language_file_has_the_same_keys_in_every_language(string $file): void
    {
        $keys = [];
        foreach (self::LOCALES as $locale) {
            $path = lang_path("{$locale}/{$file}.php");
            $this->assertFileExists($path, "lang/{$locale}/{$file}.php is missing");
            $keys[$locale] = array_keys(Arr::dot(require $path));
            sort($keys[$locale]);
        }

        foreach (['tr', 'en'] as $locale) {
            $this->assertSame([], array_values(array_diff($keys['ar'], $keys[$locale])), "{$locale}/{$file}.php lacks keys that ar has");
            $this->assertSame([], array_values(array_diff($keys[$locale], $keys['ar'])), "{$locale}/{$file}.php has keys that ar lacks");
        }
    }

    public function test_no_translation_is_left_empty(): void
    {
        foreach (self::LOCALES as $locale) {
            foreach (glob(lang_path("{$locale}/*.php")) as $path) {
                foreach (Arr::dot(require $path) as $key => $value) {
                    // List items may be deliberate blank lines (e.g. the sample WhatsApp message).
                    if (is_string($value) && ! ctype_digit((string) last(explode('.', $key)))) {
                        $this->assertNotSame('', trim($value), basename($path)." [{$locale}] {$key} is empty");
                    }
                }
            }
        }
    }
}
