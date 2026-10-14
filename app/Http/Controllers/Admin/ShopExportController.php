<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * All shops + owner contact details as a CSV (opens in Excel / Google Sheets).
 * Uses the same search & filter as the list, so you export what you see.
 */
class ShopExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $search = trim((string) $request->query('q', ''));
        $filter = in_array($request->query('status'), ShopController::FILTERS, true) ? $request->query('status') : 'all';

        $query = ShopController::filtered(Shop::query(), $search, $filter)
            ->with('owner')
            ->withCount('products')
            ->oldest();

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM: Excel shows Arabic & Turkish correctly

            fputcsv($out, [
                'Shop', 'Menu link', 'Owner', 'Email', 'WhatsApp', 'Currency', 'Languages', 'Products', 'Status',
                'Registered at', 'Last login', 'Trial ends', 'Paid until', 'Billing',
            ]);

            // Streams in chunks: fine with thousands of shops.
            $query->chunk(200, function ($shops) use ($out) {
                foreach ($shops as $shop) {
                    fputcsv($out, array_map(self::safe(...), [
                        $shop->getTranslation('name', $shop->default_locale),
                        $shop->menuUrl(),
                        $shop->owner?->name,
                        $shop->owner?->email,
                        $shop->whatsapp_number,
                        $shop->currency,
                        implode(' ', $shop->contentLocales()),
                        $shop->products_count,
                        $shop->is_active ? 'active' : 'suspended',
                        $shop->created_at->format('Y-m-d H:i'),
                        $shop->owner?->last_login_at?->format('Y-m-d H:i'),
                        $shop->trial_ends_at?->toDateString(),
                        $shop->paid_until?->toDateString(),
                        $shop->billingStatus(),
                    ]));
                }
            });

            fclose($out);
        }, 'sofra-shops-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Shop and owner names are typed by users. A value like "=HYPERLINK(…)"
     * would run as a formula in Excel ("CSV injection"); a leading
     * apostrophe makes spreadsheets treat it as plain text.
     */
    private static function safe(mixed $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
