<?php

namespace App\Support;

use App\Models\Shop;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use ReflectionClass;

/**
 * QR codes for a shop's menu link, in the shop's brand color.
 *
 * Scanning safety first: the dark modules use the brand color darkened until
 * it has at least 7:1 contrast on white, the three corner "eyes" stay square,
 * and error correction is "M" (survives ~15% damage — coffee stains happen).
 */
class MenuQrCode
{
    /**
     * @param  string|null  $url  defaults to the shop's menu link
     */
    public function __construct(private ?Shop $shop, private ?string $url = null) {}

    public function url(): string
    {
        return $this->url ?? $this->shop->menuUrl();
    }

    public function svg(): string
    {
        return (new QRCode($this->options([
            'outputInterface' => QRMarkupSVG::class,
            'connectPaths' => true,
            'moduleValues' => $this->darkModules($this->color()),
        ])))->render($this->url());
    }

    /**
     * High-resolution PNG (~1200px) for printing.
     */
    public function png(): string
    {
        return (new QRCode($this->options([
            'outputInterface' => QRGdImagePNG::class,
            'scale' => 32,
            'bgColor' => [255, 255, 255],
            'moduleValues' => $this->darkModules(Color::rgb($this->color())),
        ])))->render($this->url());
    }

    public function color(): string
    {
        return Color::readableOn($this->shop?->brand_color ?? config('sofra.defaults.brand_color'), '#ffffff', 7);
    }

    private function options(array $overrides): QROptions
    {
        return new QROptions([
            'eccLevel' => EccLevel::M,
            'outputBase64' => false,
            'addQuietzone' => true,
            'quietzoneSize' => 4, // the blank margin the QR standard asks for, so printed cards scan reliably
            'drawLightModules' => false,
            'drawCircularModules' => true,
            'circleRadius' => 0.42,
            'keepAsSquare' => [QRMatrix::M_FINDER_DARK, QRMatrix::M_FINDER_DOT, QRMatrix::M_ALIGNMENT_DARK],
            ...$overrides,
        ]);
    }

    /**
     * Every "dark" module type gets the same color.
     *
     * @return array<int, mixed>
     */
    private function darkModules(mixed $value): array
    {
        $constants = (new ReflectionClass(QRMatrix::class))->getConstants();

        return collect($constants)
            ->filter(fn ($type, string $name) => str_starts_with($name, 'M_') && is_int($type)
                && ($type & QRMatrix::IS_DARK) === QRMatrix::IS_DARK)
            ->mapWithKeys(fn (int $type) => [$type => $value])
            ->all();
    }
}
