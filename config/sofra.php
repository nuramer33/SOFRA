<?php

/*
|--------------------------------------------------------------------------
| Sofra platform settings
|--------------------------------------------------------------------------
| Everything that is "product configuration" (not secrets, not environment)
| lives here so it is easy to find and change in one place.
*/

return [

    // Supported interface + content languages. The first one is the default.
    // `dir` drives <html dir="..."> so RTL is decided in exactly one place.
    'locales' => [
        'ar' => ['name' => 'العربية', 'dir' => 'rtl'],
        'tr' => ['name' => 'Türkçe', 'dir' => 'ltr'],
        'en' => ['name' => 'English', 'dir' => 'ltr'],
    ],

    // Public menus live at the root: sofra.app/{slug}. These words can never
    // be a shop slug because they are (or will be) our own routes.
    'reserved_slugs' => [
        'admin', 'api', 'app', 'assets', 'auth', 'billing', 'blog', 'build', 'dashboard',
        'docs', 'favicon', 'forgot-password', 'help', 'home', 'images', 'locale', 'login',
        'logout', 'menu', 'pricing', 'privacy', 'register', 'reset-password', 'robots',
        'settings', 'shop', 'shops', 'signup', 'sitemap', 'sofra', 'storage', 'support',
        'stripe', 'terms', 'up', 'vendor', 'www',
    ],

    // Currencies a shop can price its menu in (ISO 4217).
    'currencies' => [
        'TRY', 'SAR', 'AED', 'KWD', 'QAR', 'BHD', 'OMR', 'JOD',
        'IQD', 'EGP', 'SYP', 'LBP', 'MAD', 'USD', 'EUR',
    ],

    // Time zones a shop can pick for its ordering hours (the first is the default).
    'timezones' => [
        'Europe/Istanbul', 'Asia/Riyadh', 'Asia/Dubai', 'Asia/Kuwait', 'Asia/Qatar', 'Asia/Bahrain',
        'Asia/Muscat', 'Asia/Baghdad', 'Asia/Amman', 'Asia/Beirut', 'Asia/Damascus', 'Africa/Cairo',
        'Africa/Casablanca', 'Europe/London', 'Europe/Berlin', 'America/New_York', 'UTC',
    ],

    // Curated brand colors offered as one-tap swatches (any hex is allowed).
    'brand_presets' => [
        '#A8462A', '#3B2A20', '#56653A', '#B7791F',
        '#B4546A', '#1F6F6B', '#263A5C', '#6B3A5E',
    ],

    // Uploaded images are re-encoded to WebP at these sizes (longest side, px).
    'images' => [
        'max_upload_kb' => 10240,
        'max_dimension' => 8000,     // reject absurd "pixel bomb" files
        'quality' => 80,
        'sizes' => [
            'product' => ['lg' => 1200, 'sm' => 480],
            'category' => ['lg' => 800, 'sm' => 320],
            'logo' => ['lg' => 512, 'sm' => 128],
            'review' => ['lg' => 1200, 'sm' => 480],
        ],
    ],

    // Subscription. There are no online payments: an admin records payments
    // by hand (a payment gateway can later call Shop::recordPayment()).
    // States: trial → paid → past_due (grace period, everything still works)
    // → expired. A manually suspended shop is a separate switch (is_active).
    // Nothing is ever deleted when a subscription ends.
    'billing' => [
        'trial_days' => 60,
        'grace_days' => 7,                             // days after the due date before "expired"
        'ending_soon_days' => 7,                       // "trial ending" warning window
        'reminder_days' => [7, 3, 1],                  // owner emails: days before the due date
        // What the PUBLIC menu does once a shop is expired:
        //   visible    → nothing changes, ordering keeps working
        //   read_only  → menu stays visible, ordering is switched off (default)
        //   suspended  → menu is hidden (404)
        'expired_menu' => env('SOFRA_EXPIRED_MENU', 'read_only'),

        // Automatic monthly payment through Stripe (leave the keys empty to keep
        // payments manual). `price` is the id of a recurring monthly Price you
        // create once in the Stripe dashboard (price_…).
        'stripe' => [
            'secret' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'price' => env('STRIPE_PRICE_ID'),
            'api_version' => env('STRIPE_API_VERSION', '2024-06-20'),
        ],
        'monthly_price' => env('SOFRA_MONTHLY_PRICE', '$5'), // shown on the landing page
        'yearly_price' => env('SOFRA_YEARLY_PRICE', '$50'), // yearly offer, shown next to the monthly price
        'support_whatsapp' => env('SOFRA_SUPPORT_WHATSAPP'), // digits, e.g. 905551234567
        'support_email' => env('SOFRA_SUPPORT_EMAIL'),
        'support_instagram' => env('SOFRA_SUPPORT_INSTAGRAM', 'norha_ndesign'), // handle without the @
    ],

    // Nightly backup (php artisan sofra:backup): database dump + uploaded images.
    'backup' => [
        'path' => storage_path('app/backups'),
        'keep_days' => (int) env('SOFRA_BACKUP_KEEP_DAYS', 14),
        'mysqldump' => env('SOFRA_MYSQLDUMP', 'mysqldump'),
    ],

    // Shop shown as a live example on the landing page (if it exists).
    'demo_slug' => 'demo',

    // Public menu looks a shop can choose (resources/views/menu/templates/{name}.blade.php).
    'templates' => ['classic', 'luxe'],

    // Defaults for a freshly registered shop.
    'defaults' => [
        'brand_color' => '#55642F',
        'currency' => 'TRY',
        'template' => 'classic',
    ],

];
