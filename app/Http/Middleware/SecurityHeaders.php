<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cheap, safe browser protections on every page.
 *
 * No Content-Security-Policy on purpose: Alpine.js evaluates expressions and
 * our pages use small inline scripts, so a strict policy needs a refactor.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN'); // nobody can frame our pages (clickjacking)
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // Do not advertise the PHP version, whatever the host's php.ini says.
        header_remove('X-Powered-By');

        // Private screens must never show up in search results.
        if ($request->is('dashboard', 'dashboard/*', 'admin', 'admin/*', 'login', 'register', 'forgot-password', 'reset-password/*', 'email/*', 'locale/*')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        if ($request->isSecure() && app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
