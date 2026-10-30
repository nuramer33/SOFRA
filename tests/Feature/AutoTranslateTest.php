<?php

namespace Tests\Feature;

use App\Support\AutoTranslator;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AutoTranslateTest extends TestCase
{
    public function test_missing_languages_are_translated_from_the_main_one(): void
    {
        config(['sofra.auto_translate' => true]);
        Http::fake(fn ($request) => Http::response([[[$request['tl'].':'.$request['q'], $request['q']]]]));

        $texts = app(AutoTranslator::class)->fill(['ar' => 'قهوة'], ['ar', 'tr', 'en']);

        $this->assertSame(['ar' => 'قهوة', 'tr' => 'tr:قهوة', 'en' => 'en:قهوة'], $texts);
    }

    public function test_texts_the_owner_wrote_are_kept(): void
    {
        config(['sofra.auto_translate' => true]);
        Http::fake(fn ($request) => Http::response([[['x', 'y']]]));

        $texts = app(AutoTranslator::class)->fill(['ar' => 'قهوة', 'en' => 'Coffee'], ['ar', 'tr', 'en']);

        $this->assertSame('Coffee', $texts['en']);
    }

    public function test_an_unreachable_service_leaves_the_other_languages_empty(): void
    {
        config(['sofra.auto_translate' => true]);
        Http::fake(fn () => Http::response('', 500));

        $this->assertSame(['ar' => 'شاي'], app(AutoTranslator::class)->fill(['ar' => 'شاي'], ['ar', 'tr']));
    }
}
