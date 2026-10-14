<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Tenancy\CurrentShop;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(CurrentShop $currentShop): View
    {
        $shop = $currentShop->get();

        // These counts are automatically limited to this shop by ShopScope.
        $categoryCount = Category::count();
        $productCount = Product::count();

        $steps = [
            'account' => true,
            'identity' => $shop->logo_path !== null,
            'whatsapp' => filled($shop->whatsapp_number),
            'category' => $categoryCount > 0,
            'product' => $productCount > 0,
            'share' => $shop->qr_downloaded_at !== null,
        ];

        return view('dashboard.index', [
            'shop' => $shop,
            'categoryCount' => $categoryCount,
            'productCount' => $productCount,
            'steps' => $steps,
            'menuUrl' => url($shop->slug),
        ]);
    }
}
