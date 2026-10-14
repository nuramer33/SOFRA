<?php

use App\Http\Middleware\EnsureSubscriptionActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetTenantFromUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [SetLocale::class, SecurityHeaders::class]);

        // Nginx on the same server terminates HTTPS and forwards to PHP-FPM, so
        // the forwarded scheme/host headers can be trusted (needed for correct
        // https:// links and secure cookies behind the proxy).
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'tenant' => SetTenantFromUser::class,
            'admin' => EnsureUserIsAdmin::class,
            'subscribed' => EnsureSubscriptionActive::class,
        ]);

        // The tenant must be known BEFORE route model binding resolves
        // {product} / {category}, so the ShopScope filters that lookup too.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: SetTenantFromUser::class,
        );

        // The menu's order endpoint only builds a WhatsApp message and changes
        // nothing — so a menu left open for hours never fails with "419".
        $middleware->validateCsrfTokens(except: ['*/order', '*/review', '*/track', 'stripe/webhook']);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->homeUrl() ?? route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
