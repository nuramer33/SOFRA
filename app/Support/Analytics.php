<?php

namespace App\Support;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Privacy-friendly analytics: counts how many times something happened, per
 * shop, day and product — nothing else. No names, phones, addresses, carts,
 * order contents, IPs or device ids are stored.
 *
 * A "WhatsApp click" means the customer pressed the order button and WhatsApp
 * was opened. It is NOT a confirmed order: what happens in the chat is unknown.
 */
class Analytics
{
    public const MENU_VIEW = 'menu_view';

    public const PRODUCT_VIEW = 'product_view';

    public const ADD_TO_CART = 'add_to_cart';

    public const WHATSAPP_CLICK = 'whatsapp_click';

    public const EVENTS = [self::MENU_VIEW, self::PRODUCT_VIEW, self::ADD_TO_CART, self::WHATSAPP_CLICK];

    /** Days a shop can keep its counters. */
    public const RETENTION_OPTIONS = [30, 90, 180, 365];

    public const DEFAULT_RETENTION = 90;

    /** Same visitor, same event, same product: counted once per this many seconds. */
    private const DEDUPE_SECONDS = [
        self::MENU_VIEW => 1800,
        self::PRODUCT_VIEW => 300,
        self::ADD_TO_CART => 5,
        self::WHATSAPP_CLICK => 30,
    ];

    /** Hard ceiling of counted events per visitor session per hour. */
    private const HOURLY_CAP = 300;

    /**
     * Counts one event. Returns false when it was skipped (bot, "do not
     * track", the owner previewing their own menu, a duplicate, or over the cap).
     */
    public static function record(Shop $shop, Request $request, string $event, ?int $productId = null): bool
    {
        if (! in_array($event, self::EVENTS, true) || self::shouldSkip($shop, $request)) {
            return false;
        }

        // The session id is only ever hashed into a short-lived cache key, never saved.
        $visitor = sha1((string) $request->session()->getId());
        $productId ??= 0;

        $duplicateKey = "analytics:seen:{$shop->id}:{$event}:{$productId}:{$visitor}";
        if (! Cache::add($duplicateKey, 1, self::DEDUPE_SECONDS[$event])) {
            return false;
        }

        $capKey = "analytics:cap:{$visitor}";
        if (RateLimiter::tooManyAttempts($capKey, self::HOURLY_CAP)) {
            return false;
        }
        RateLimiter::hit($capKey, 3600);

        DB::table('analytics_dailies')->upsert(
            [['shop_id' => $shop->id, 'day' => today()->toDateString(), 'event' => $event, 'product_id' => $productId, 'total' => 1]],
            ['shop_id', 'day', 'event', 'product_id'],
            ['total' => DB::raw('total + 1')],
        );

        return true;
    }

    private static function shouldSkip(Shop $shop, Request $request): bool
    {
        if ($request->header('DNT') === '1' || $request->header('Sec-GPC') === '1') {
            return true;
        }

        // Link previews and crawlers (WhatsApp, Google…) are not customers.
        if (preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|headless/i', (string) $request->userAgent())) {
            return true;
        }

        return $request->user()?->shop_id === $shop->id;
    }
}
