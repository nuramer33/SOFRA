<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\MenuQrCode;
use App\Tenancy\CurrentShop;
use Illuminate\Http\Response;
use Illuminate\View\View;

class QrCodeController extends Controller
{
    public function show(CurrentShop $currentShop): View
    {
        $shop = $currentShop->get();

        return view('dashboard.qr', ['shop' => $shop, 'qr' => new MenuQrCode($shop)]);
    }

    /**
     * @param  'svg'|'png'  $format
     */
    public function download(CurrentShop $currentShop, string $format): Response
    {
        $shop = $currentShop->get();
        $qr = new MenuQrCode($shop);
        $this->markShared($currentShop);

        return response($format === 'svg' ? $qr->svg() : $qr->png(), 200, [
            'Content-Type' => $format === 'svg' ? 'image/svg+xml' : 'image/png',
            'Content-Disposition' => "attachment; filename=\"menu-qr-{$shop->slug}.{$format}\"",
        ]);
    }

    /**
     * A print-ready table card (A6) with logo, name, QR and the link.
     */
    public function print(CurrentShop $currentShop): View
    {
        $shop = $currentShop->get();
        $this->markShared($currentShop);

        return view('dashboard.qr-print', ['shop' => $shop, 'qr' => new MenuQrCode($shop)]);
    }

    /** Completes the "share your QR" step on the dashboard checklist. */
    private function markShared(CurrentShop $currentShop): void
    {
        $shop = $currentShop->get();

        if ($shop->qr_downloaded_at === null) {
            $shop->forceFill(['qr_downloaded_at' => now()])->save();
        }
    }
}
