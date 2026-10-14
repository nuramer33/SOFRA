<?php

namespace App\Support;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncodedImageInterface;

/**
 * The 1200x630 picture shown when a menu link is shared (WhatsApp, Instagram,
 * Telegram...). Built from the shop's own photos, in this order:
 *   1. a featured product photo, 2. any product photo, 3. the logo on the
 *   brand color, 4. just the brand color with an arch.
 * Generated once, saved on disk, and rebuilt automatically when any of the
 * ingredients change (the file name carries a fingerprint of them).
 */
class ShareImage
{
    private const DISK = 'public';

    private const WIDTH = 1200;

    private const HEIGHT = 630;

    public function __construct(private ImageManager $images) {}

    /**
     * Public URL of the share image (the fingerprint busts the chat apps' caches).
     */
    public function url(Shop $shop): string
    {
        return route('menu.share-image', ['shop' => $shop, 'v' => substr($this->fingerprint($shop), 0, 8)]);
    }

    /**
     * Path of the up-to-date image on the public disk, creating it if needed.
     */
    public function path(Shop $shop): string
    {
        $fingerprint = $this->fingerprint($shop);
        $path = "shops/{$shop->id}/share/{$fingerprint}.jpg";
        $disk = Storage::disk(self::DISK);

        if (! $disk->exists($path)) {
            $disk->delete($disk->files("shops/{$shop->id}/share")); // older versions
            $disk->put($path, $this->render($shop)->toString());
        }

        return $path;
    }

    private function fingerprint(Shop $shop): string
    {
        return md5(implode('|', [$shop->brand_color, $shop->logo_path, $this->photoPath($shop)]));
    }

    private function photoPath(Shop $shop): ?string
    {
        $products = Product::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('is_available', true)
            ->whereNotNull('image_path');

        return (clone $products)->where('is_featured', true)->orderBy('sort_order')->orderBy('id')->value('image_path')
            ?? $products->orderBy('sort_order')->orderBy('id')->value('image_path');
    }

    private function render(Shop $shop): EncodedImageInterface
    {
        $disk = Storage::disk(self::DISK);

        if ($photo = $this->photoPath($shop)) {
            $canvas = $this->images->decodePath($disk->path($photo))->cover(self::WIDTH, self::HEIGHT);
        } else {
            $canvas = $this->images->createImage(self::WIDTH, self::HEIGHT)->fill($shop->brand_color);
            $canvas->drawCircle(fn ($circle) => $circle->at(self::WIDTH / 2, 150)->radius(110)->background('rgba(255, 255, 255, 0.14)'));
            $canvas->drawRectangle(fn ($rectangle) => $rectangle->at(self::WIDTH / 2 - 110, 150)->size(220, 480)->background('rgba(255, 255, 255, 0.14)'));

            if ($shop->logo_path) {
                $canvas->drawCircle(fn ($circle) => $circle->at(self::WIDTH / 2, self::HEIGHT / 2)->radius(190)->background('#ffffff'));
                $logo = $this->images->decodePath($disk->path($shop->logo_path))->scaleDown(300, 300);
                $canvas->insert($logo, 0, 0, 'center');
            }
        }

        return $canvas->encode(new JpegEncoder(quality: 82));
    }
}
