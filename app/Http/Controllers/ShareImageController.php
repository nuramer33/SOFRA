<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Support\ShareImage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShareImageController extends Controller
{
    public function __invoke(Shop $shop, ShareImage $shareImage): BinaryFileResponse
    {
        abort_unless($shop->isPubliclyVisible(), 404);

        return response()->file(
            Storage::disk('public')->path($shareImage->path($shop)),
            ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'public, max-age=86400'],
        );
    }
}
