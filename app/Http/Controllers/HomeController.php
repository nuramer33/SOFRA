<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Support\MenuQrCode;
use Illuminate\View\View;

/**
 * The marketing site (landing page) that sells Sofra to shop owners.
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        // A real menu visitors can open — only linked if it exists and is live.
        $demo = Shop::where('slug', config('sofra.demo_slug'))->where('is_active', true)->first();

        return view('landing', [
            'demoUrl' => $demo?->menuUrl(),
            'qr' => new MenuQrCode($demo, $demo?->menuUrl() ?? route('home')),
            'trialMonths' => max(1, (int) round(config('sofra.billing.trial_days') / 30)),
            'monthlyPrice' => config('sofra.billing.monthly_price'),
            'yearlyPrice' => config('sofra.billing.yearly_price'),
        ]);
    }
}
