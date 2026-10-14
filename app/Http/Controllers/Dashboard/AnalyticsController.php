<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsDaily;
use App\Models\Product;
use App\Support\Analytics;
use App\Tenancy\CurrentShop;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Engagement summary for the owner's own shop (the tenant scope limits every
 * query). Shows interest, not sales: a WhatsApp click is never an order.
 */
class AnalyticsController extends Controller
{
    private const RANGES = [7, 30, 90];

    private const GROUPS = ['day', 'week', 'month'];

    public function index(Request $request, CurrentShop $currentShop): View
    {
        $shop = $currentShop->get();

        $range = in_array((int) $request->query('range'), self::RANGES, true) ? (int) $request->query('range') : 30;
        $group = in_array($request->query('group'), self::GROUPS, true) ? $request->query('group') : 'day';
        $from = today()->subDays($range - 1);

        $rows = AnalyticsDaily::query()
            ->where('day', '>=', $from->toDateString())
            ->get(['day', 'event', 'product_id', 'total']);

        $totals = collect(Analytics::EVENTS)->mapWithKeys(
            fn (string $event) => [$event => (int) $rows->where('event', $event)->sum('total')],
        )->all();

        return view('dashboard.analytics', [
            'shop' => $shop,
            'range' => $range,
            'group' => $group,
            'ranges' => self::RANGES,
            'groups' => self::GROUPS,
            'totals' => $totals,
            'series' => $this->series($rows, $from, $group),
            'topViewed' => $this->topProducts($rows, Analytics::PRODUCT_VIEW),
            'topAdded' => $this->topProducts($rows, Analytics::ADD_TO_CART),
            'retention' => $shop->analytics_retention_days,
        ]);
    }

    /**
     * One entry per day / week / month in the range, empty periods included.
     *
     * @param  Collection<int, AnalyticsDaily>  $rows
     * @return list<array{label: string, menu_view: int, product_view: int, add_to_cart: int, whatsapp_click: int}>
     */
    private function series(Collection $rows, Carbon $from, string $group): array
    {
        $bucket = fn (Carbon $day): Carbon => match ($group) {
            'week' => $day->copy()->startOfWeek(),
            'month' => $day->copy()->startOfMonth(),
            default => $day->copy()->startOfDay(),
        };

        $buckets = [];
        foreach (Carbon::parse($from)->toPeriod(today()) as $day) {
            $key = $bucket($day)->toDateString();
            $buckets[$key] ??= ['date' => $bucket($day), ...array_fill_keys(Analytics::EVENTS, 0)];
        }

        foreach ($rows as $row) {
            $key = $bucket($row->day)->toDateString();
            if (isset($buckets[$key])) {
                $buckets[$key][$row->event] += $row->total;
            }
        }

        $format = match ($group) {
            'month' => 'F Y',
            'week' => 'j M',
            default => 'D j M',
        };

        return array_values(array_map(
            fn (array $entry) => [...$entry, 'label' => $entry['date']->locale(app()->getLocale())->translatedFormat($format)],
            $buckets,
        ));
    }

    /**
     * Most viewed / most added products. Products that no longer exist are left out.
     *
     * @param  Collection<int, AnalyticsDaily>  $rows
     * @return Collection<int, array{product: Product, total: int}>
     */
    private function topProducts(Collection $rows, string $event): Collection
    {
        $totals = $rows->where('event', $event)->where('product_id', '>', 0)
            ->groupBy('product_id')->map(fn (Collection $group) => (int) $group->sum('total'))
            ->sortDesc()->take(10);

        $products = Product::query()->whereKey($totals->keys())->get()->keyBy('id');

        return $totals
            ->filter(fn (int $total, int $id) => $products->has($id))
            ->map(fn (int $total, int $id) => ['product' => $products->get($id), 'total' => $total])
            ->values();
    }
}
