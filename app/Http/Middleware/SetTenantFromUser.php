<?php

namespace App\Http\Middleware;

use App\Tenancy\CurrentShop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * For dashboard routes: "the current shop is the logged-in owner's shop".
 * Runs after `auth`, so a user is guaranteed.
 */
class SetTenantFromUser
{
    public function __construct(private CurrentShop $currentShop) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $shop = $user->shop;

        if ($shop === null) {
            // A platform admin without a shop belongs in the admin panel.
            abort_unless($user->isAdmin(), 403);

            return redirect()->route('admin.index');
        }

        $this->currentShop->set($shop);

        return $next($request);
    }
}
