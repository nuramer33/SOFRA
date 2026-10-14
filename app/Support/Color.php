<?php

namespace App\Support;

/**
 * Small color helpers based on WCAG contrast, so a shop's brand color is
 * always used in a readable way — whatever color the owner picks.
 */
class Color
{
    public const PAPER = '#f4f2e7';

    public const INK = '#1f2417';

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    public static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    public static function hex(array $rgb): string
    {
        return '#'.implode('', array_map(fn ($c) => str_pad(dechex((int) round($c)), 2, '0', STR_PAD_LEFT), $rgb));
    }

    public static function luminance(string $hex): float
    {
        $linear = function (int $channel): float {
            $c = $channel / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        [$r, $g, $b] = self::rgb($hex);

        return 0.2126 * $linear($r) + 0.7152 * $linear($g) + 0.0722 * $linear($b);
    }

    public static function contrast(string $a, string $b): float
    {
        [$light, $dark] = [max(self::luminance($a), self::luminance($b)), min(self::luminance($a), self::luminance($b))];

        return ($light + 0.05) / ($dark + 0.05);
    }

    /**
     * Mix two colors; $amount 0 = all $a, 1 = all $b.
     */
    public static function mix(string $a, string $b, float $amount): string
    {
        $from = self::rgb($a);
        $to = self::rgb($b);

        return self::hex(array_map(fn ($x, $y) => $x + ($y - $x) * $amount, $from, $to));
    }

    /**
     * White or ink — whichever reads better on top of $background.
     */
    public static function foregroundFor(string $background): string
    {
        return self::contrast(self::INK, $background) > self::contrast('#ffffff', $background) ? self::INK : '#ffffff';
    }

    /**
     * $color itself if it's readable on $background (≥ 4.5:1), otherwise the
     * closest darker shade that is. Keeps the brand's hue for prices & links.
     */
    public static function readableOn(string $color, string $background = self::PAPER, float $minimum = 4.5): string
    {
        for ($step = 0; $step <= 10; $step++) {
            $candidate = self::mix($color, self::INK, $step / 10);

            if (self::contrast($candidate, $background) >= $minimum) {
                return $candidate;
            }
        }

        return self::INK;
    }
}
