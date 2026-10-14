<?php

namespace App\Support;

/**
 * Number input helpers. Arabic keyboards type Arabic-Indic digits (٠١٢…)
 * and "٫" as the decimal mark, Turkish users type "12,50". We accept all of
 * them and store plain Western numbers.
 */
class Numbers
{
    private const DIGIT_MAP = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];

    public static function westernDigits(string $value): string
    {
        return strtr($value, self::DIGIT_MAP);
    }

    /**
     * "١٢٫٥" → "12.5", "12,50" → "12.50", " 85 " → "85". Non-numeric input is
     * returned (trimmed) so validation can reject it with a proper message.
     */
    public static function decimal(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $normalized = str_replace(['٫', ','], '.', self::westernDigits(trim($value)));
        $normalized = str_replace(['٬', ' ', "\u{00A0}"], '', $normalized);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * Phone number → digits only, international format without "+" or "00".
     */
    public static function phone(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', self::westernDigits($value));

        return str_starts_with($digits, '00') ? substr($digits, 2) : $digits;
    }
}
