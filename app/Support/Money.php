<?php

namespace App\Support;

use NumberFormatter;

/**
 * Price formatting we control (ICU's defaults are inconsistent across our
 * three languages — e.g. "85 TRY" in Arabic but "₺85" in Turkish).
 *
 * Rules: Western digits everywhere, no decimals for whole prices,
 * Arabic symbols for Arab currencies when the interface is Arabic.
 */
class Money
{
    /** @var array<string, array{symbol: string, ar?: string}> */
    private const SYMBOLS = [
        'TRY' => ['symbol' => '₺'],
        'USD' => ['symbol' => '$'],
        'EUR' => ['symbol' => '€'],
        'SAR' => ['symbol' => 'SAR', 'ar' => 'ر.س'],
        'AED' => ['symbol' => 'AED', 'ar' => 'د.إ'],
        'KWD' => ['symbol' => 'KWD', 'ar' => 'د.ك'],
        'QAR' => ['symbol' => 'QAR', 'ar' => 'ر.ق'],
        'BHD' => ['symbol' => 'BHD', 'ar' => 'د.ب'],
        'OMR' => ['symbol' => 'OMR', 'ar' => 'ر.ع'],
        'JOD' => ['symbol' => 'JOD', 'ar' => 'د.أ'],
        'IQD' => ['symbol' => 'IQD', 'ar' => 'د.ع'],
        'EGP' => ['symbol' => 'EGP', 'ar' => 'ج.م'],
        'SYP' => ['symbol' => 'SYP', 'ar' => 'ل.س'],
        'LBP' => ['symbol' => 'LBP', 'ar' => 'ل.ل'],
        'MAD' => ['symbol' => 'MAD', 'ar' => 'د.م'],
    ];

    public static function symbol(string $currency, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $entry = self::SYMBOLS[$currency] ?? ['symbol' => $currency];

        return $entry[$locale] ?? $entry['symbol'];
    }

    public static function format(float|int|string $amount, string $currency, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $amount = (float) $amount;

        $formatter = new NumberFormatter($locale.'@numbers=latn', NumberFormatter::DECIMAL);
        $decimals = fmod($amount, 1.0) == 0.0 ? 0 : 2;
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals);

        $number = $formatter->format($amount);
        $symbol = self::symbol($currency, $locale);

        // "$12.50" / "€8" read naturally in English; everything else is "85 ₺".
        return $locale === 'en' && mb_strlen($symbol) === 1 ? $symbol.$number : $number.' '.$symbol;
    }
}
