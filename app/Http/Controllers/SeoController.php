<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Response;

/**
 * robots.txt and sitemap.xml, generated so they always use the real domain
 * and list every live menu.
 */
class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /dashboard',
            'Disallow: /admin',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            'Disallow: /locale/',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        // Only menus that are live and actually have something to show.
        $shops = Shop::query()
            ->where('is_active', true)
            ->whereIn('id', Product::withoutGlobalScopes()->where('is_available', true)->select('shop_id'))
            ->orderBy('id')
            ->get()
            ->filter(fn (Shop $shop) => $shop->isPubliclyVisible());

        return response()
            ->view('sitemap', ['shops' => $shops])
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
