<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Dashboard\AccountController;
use App\Http\Controllers\Dashboard\AnalyticsController;
use App\Http\Controllers\Dashboard\BillingController;
use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\OrderingController;
use App\Http\Controllers\Dashboard\PositionController;
use App\Http\Controllers\Dashboard\ProductAvailabilityController;
use App\Http\Controllers\Dashboard\ProductController;
use App\Http\Controllers\Dashboard\ProductQuickController;
use App\Http\Controllers\Dashboard\ProductSuggestionController;
use App\Http\Controllers\Dashboard\QrCodeController;
use App\Http\Controllers\Dashboard\ReviewController as DashboardReviewController;
use App\Http\Controllers\Dashboard\ShopSettingsController;
use App\Http\Controllers\Dashboard\SubscriptionPaymentController;
use App\Http\Controllers\Dashboard\SuggestionActivationController;
use App\Http\Controllers\Dashboard\TodayController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ShareImageController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TrackController;
use App\Support\Locales;
use Illuminate\Support\Facades\Route;

// Marketing site.
Route::get('/', HomeController::class)->name('home');

Route::get('robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

// Stripe's server-to-server notifications (signature-checked, no session, no CSRF).
Route::post('stripe/webhook', StripeWebhookController::class)->middleware('throttle:120,1')->name('stripe.webhook');

Route::get('privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('terms', [LegalController::class, 'terms'])->name('legal.terms');

Route::get('locale/{locale}', LocaleController::class)
    ->whereIn('locale', Locales::codes())
    ->name('locale.switch');

/*
| Guests only
*/
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])->middleware('throttle:10,1');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')->name('logout');

// Email confirmation. Soft: unconfirmed owners keep working, with a reminder banner.
Route::middleware('auth')->group(function () {
    Route::get('email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('email/verification-notification', EmailVerificationNotificationController::class)
        ->middleware('throttle:3,1')->name('verification.send');
});

/*
| Shop dashboard — `tenant` locks every query (and every {model} in the URL)
| to the owner's shop.
*/
Route::middleware(['auth', 'tenant', 'subscribed'])->prefix('dashboard')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('billing', [BillingController::class, 'show'])->name('billing.show');
    Route::post('billing/checkout', [SubscriptionPaymentController::class, 'checkout'])->middleware('throttle:10,1')->name('billing.checkout');
    Route::post('billing/portal', [SubscriptionPaymentController::class, 'portal'])->middleware('throttle:10,1')->name('billing.portal');

    // Quick, phone-friendly changes (all answer JSON so the page updates in place).
    Route::get('today', TodayController::class)->name('today');
    Route::patch('products/{product}/quick', [ProductQuickController::class, 'update'])->whereNumber('product')->name('products.quick');
    Route::patch('offers/{suggestion}/active', [SuggestionActivationController::class, 'update'])->whereNumber('suggestion')->name('offers.active');
    Route::get('ordering', [OrderingController::class, 'edit'])->name('ordering.edit');
    Route::put('ordering', [OrderingController::class, 'update'])->name('ordering.update');
    Route::patch('ordering/pause', [OrderingController::class, 'pause'])->name('ordering.pause');

    Route::resource('categories', CategoryController::class)->except('show');
    Route::patch('categories/{category}/position', [PositionController::class, 'category'])->name('categories.position');

    Route::resource('products', ProductController::class)->except('show');
    Route::patch('products/{product}/position', [PositionController::class, 'product'])->name('products.position');
    Route::get('products/{product}/suggestions', [ProductSuggestionController::class, 'edit'])->name('products.suggestions.edit');
    Route::put('products/{product}/suggestions', [ProductSuggestionController::class, 'update'])->name('products.suggestions.update');
    Route::patch('products/{product}/availability', [ProductAvailabilityController::class, 'update'])->name('products.availability');

    Route::get('qr', [QrCodeController::class, 'show'])->name('qr.show');
    Route::get('qr/print', [QrCodeController::class, 'print'])->name('qr.print');
    Route::get('qr/download/{format}', [QrCodeController::class, 'download'])->whereIn('format', ['svg', 'png'])->name('qr.download');

    Route::get('account', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('account', [AccountController::class, 'update'])->name('account.update');
    Route::put('account/password', [AccountController::class, 'password'])->name('account.password');

    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    Route::get('reviews', [DashboardReviewController::class, 'index'])->name('reviews.index');
    Route::delete('reviews/{review}', [DashboardReviewController::class, 'destroy'])->whereNumber('review')->name('reviews.destroy');

    Route::get('settings', [ShopSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [ShopSettingsController::class, 'update'])->name('settings.update');
});

/*
| Platform admin — only accounts made with `php artisan sofra:admin`.
| Everyone else gets a 404 (see EnsureUserIsAdmin).
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\ShopController::class, 'index'])->name('index');
    Route::get('shops/export', Admin\ShopExportController::class)->name('shops.export');
    Route::get('shops/{shop}', [Admin\ShopController::class, 'show'])->whereNumber('shop')->name('shops.show');
    Route::patch('shops/{shop}/status', [Admin\ShopStatusController::class, 'update'])->whereNumber('shop')->name('shops.status');
    Route::post('shops/{shop}/extensions', [Admin\ShopExtensionController::class, 'store'])->whereNumber('shop')->name('shops.extensions');
    Route::post('shops/{shop}/payments', [Admin\ShopPaymentController::class, 'store'])->whereNumber('shop')->name('shops.payments');
});

/*
| Public menus: sofra.app/{slug}. Registered LAST so every real route above
| wins; reserved words (config/sofra.php) can never become a slug.
*/
Route::pattern('shop', '[a-z0-9](?:[a-z0-9-]*[a-z0-9])?');

Route::get('{shop:slug}', [MenuController::class, 'show'])->name('menu.show');
Route::get('{shop:slug}/share.jpg', ShareImageController::class)->name('menu.share-image');

// Builds the WhatsApp message from the cart. Stores nothing, so it's
// excluded from CSRF (see bootstrap/app.php) but rate limited.
Route::post('{shop:slug}/order', [OrderController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('menu.order');

// Anonymous counters for the owner's analytics (views, add-to-cart). Same CSRF
// exemption as the order endpoint; the extra throttle and per-visitor
// de-duplication in Analytics::record() keep it from being abused.
Route::post('{shop:slug}/track', [TrackController::class, 'store'])
    ->middleware('throttle:90,1')
    ->name('menu.track');

// Customer reviews: stored for the owner, never shown publicly. Excluded from
// CSRF like the order endpoint (a menu can stay open for hours), but rate limited.
Route::post('{shop:slug}/review', [ReviewController::class, 'store'])
    ->middleware('throttle:5,60')
    ->name('menu.review');
