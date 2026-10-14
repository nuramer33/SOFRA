<?php

namespace App\Support;

use App\Models\Shop;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Exceptions\ImageException;
use Intervention\Image\ImageManager;

/**
 * Turns an uploaded photo into small, safe WebP files.
 *
 * Why re-encode instead of storing the original?
 *  - Size: a 6 MB phone photo becomes ~120 KB, which matters on mobile data.
 *  - Safety: decoding + re-encoding throws away anything that isn't pixels
 *    (EXIF/GPS data, hidden scripts in "polyglot" files).
 *
 * Each upload produces one file per size in config('sofra.images.sizes.{kind}'):
 *   shops/7/product/9b2…e1.webp      ← "lg" (the path stored in the database)
 *   shops/7/product/9b2…e1_sm.webp   ← "sm" thumbnail
 */
class ImageStore
{
    private const DISK = 'public';

    public function __construct(private ImageManager $images) {}

    /**
     * @param  'product'|'category'|'logo'|'review'  $kind
     * @return string The stored "lg" path.
     */
    public function store(UploadedFile $file, Shop $shop, string $kind): string
    {
        try {
            $image = $this->images->decodePath($file->getRealPath());
        } catch (ImageException) {
            throw ValidationException::withMessages(['image' => __('validation.image', ['attribute' => __('app.fields.image')])]);
        }

        $path = "shops/{$shop->id}/{$kind}/".Str::uuid().'.webp';
        $encoder = new WebpEncoder(quality: config('sofra.images.quality'), strip: true);

        // Sizes are listed largest first, so each step only scales down further.
        foreach (config("sofra.images.sizes.{$kind}") as $variant => $maxSide) {
            $encoded = $image->scaleDown($maxSide, $maxSide)->encode($encoder);
            Storage::disk(self::DISK)->put(self::variantPath($path, $variant), $encoded->toString());
        }

        return $path;
    }

    /**
     * Delete every size of a stored image. Safe to call with null.
     */
    public function delete(?string $path): void
    {
        if ($path === null) {
            return;
        }

        Storage::disk(self::DISK)->delete([$path, self::variantPath($path, 'sm')]);
    }

    public static function url(?string $path, string $variant = 'lg'): ?string
    {
        return $path === null ? null : Storage::disk(self::DISK)->url(self::variantPath($path, $variant));
    }

    public static function variantPath(string $path, string $variant): string
    {
        return $variant === 'lg' ? $path : Str::replaceLast('.webp', "_{$variant}.webp", $path);
    }
}
