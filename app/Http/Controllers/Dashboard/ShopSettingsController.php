<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ShopSettingsRequest;
use App\Models\Shop;
use App\Support\ImageStore;
use App\Support\Locales;
use App\Tenancy\CurrentShop;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ShopSettingsController extends Controller
{
    public function edit(CurrentShop $currentShop): View
    {
        return view('dashboard.settings', ['shop' => $currentShop->get()]);
    }

    public function update(ShopSettingsRequest $request, ImageStore $images): RedirectResponse
    {
        $shop = $request->shop();
        $locales = Locales::codes();

        $shop->replaceTranslations('name', $request->translations('name', $locales));
        $shop->replaceTranslations('tagline', $request->translations('tagline', $locales));
        $shop->replaceTranslations('address', $request->translations('address', $locales));
        $shop->replaceTranslations('hours', $request->translations('hours', $locales));
        $shop->fill($request->safe()->only([
            'slug', 'brand_color', 'whatsapp_number', 'currency', 'default_locale',
            'maps_url', 'instagram', 'delivery_fee', 'min_order', 'analytics_retention_days', 'template',
        ]));
        // Keep a fixed order regardless of checkbox order.
        $shop->enabled_locales = array_values(array_intersect($locales, $request->validated('enabled_locales')));
        $shop->order_types = array_values(array_intersect(Shop::ORDER_TYPES, $request->validated('order_types')));

        if ($request->hasFile('logo') || $request->boolean('remove_logo')) {
            $images->delete($shop->logo_path);
            $shop->logo_path = $request->hasFile('logo')
                ? $images->store($request->file('logo'), $shop, 'logo')
                : null;
        }

        $shop->save();

        return redirect()->route('settings.edit')->with('status', __('app.settings.saved'));
    }
}
