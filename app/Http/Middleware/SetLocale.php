<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the interface language for this request, in priority order:
 *   1. what the visitor chose with the language switcher (session)
 *   2. the logged-in owner's saved preference
 *   3. the phone/browser language (Accept-Language), if we support it
 *   4. Arabic (the platform default)
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = collect([
            $request->session()->get('locale'),
            $request->user()?->locale,
            Locales::fromBrowser($request),
        ])->first(fn ($candidate) => Locales::supports($candidate), Locales::default());

        app()->setLocale($locale);

        return $next($request);
    }
}
