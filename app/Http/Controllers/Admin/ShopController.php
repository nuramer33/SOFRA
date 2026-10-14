<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Platform admin: every shop on Sofra, with its owner's contact details.
 *
 * No tenant is set on admin routes, so the shop scope is inactive here
 * on purpose — admins see across all shops.
 */
class ShopController extends Controller
{
    /** Allowed values for ?status= */
    public const FILTERS = ['all', 'trial', 'due', 'expired', 'paid', 'suspended', 'empty', 'no_whatsapp'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $filter = in_array($request->query('status'), self::FILTERS, true) ? $request->query('status') : 'all';

        $shops = self::filtered(Shop::query(), $search, $filter)
            ->with('owner')
            ->withCount('products')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.index', [
            'shops' => $shops,
            'search' => $search,
            'filter' => $filter,
            'stats' => [
                'shops' => Shop::count(),
                'new_week' => Shop::where('created_at', '>=', now()->subDays(7))->count(),
                'trial' => Shop::billing(Shop::BILLING_TRIAL)->count(),
                'due' => Shop::billing(Shop::BILLING_DUE)->count(),
                'paid' => Shop::billing(Shop::BILLING_PAID)->count(),
                'with_whatsapp' => Shop::whereNotNull('whatsapp_number')->where('whatsapp_number', '!=', '')->count(),
                'products' => Product::withoutGlobalScopes()->count(),
                'empty' => Shop::doesntHave('products')->count(),
            ],
        ]);
    }

    public function show(Shop $shop): View
    {
        $shop->load(['users' => fn ($query) => $query->orderBy('id')])
            ->loadCount('products')
            ->load(['categories' => fn ($query) => $query->withoutGlobalScopes()->withCount('products')])
            ->load(['subscriptionEvents' => fn ($query) => $query->with('user')->latest('created_at')->latest('id')->limit(10)]);

        return view('admin.show', ['shop' => $shop]);
    }

    /**
     * Shared by the list and the CSV export so both show the same shops.
     */
    public static function filtered(Builder $query, string $search, string $filter): Builder
    {
        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';

            $query->where(fn (Builder $where) => $where
                ->where('slug', 'like', $like)
                ->orWhere('name', 'like', $like)
                ->orWhere('whatsapp_number', 'like', $like)
                ->orWhereHas('users', fn (Builder $users) => $users
                    ->where('email', 'like', $like)
                    ->orWhere('name', 'like', $like)));
        }

        return match ($filter) {
            'trial', 'due', 'expired', 'paid' => $query->billing($filter),
            'suspended' => $query->where('is_active', false),
            'empty' => $query->doesntHave('products'),
            'no_whatsapp' => $query->where(fn (Builder $where) => $where->whereNull('whatsapp_number')->orWhere('whatsapp_number', '')),
            default => $query,
        };
    }
}
