<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\OrderingRequest;
use App\Tenancy\CurrentShop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * When customers can order: a pause switch and optional weekly hours.
 * The menu itself stays visible either way.
 */
class OrderingController extends Controller
{
    public function edit(CurrentShop $currentShop): View
    {
        return view('dashboard.ordering', ['shop' => $currentShop->get()]);
    }

    public function update(OrderingRequest $request, CurrentShop $currentShop): RedirectResponse
    {
        $currentShop->get()->update([
            'orders_paused' => $request->boolean('orders_paused'),
            'timezone' => $request->validated('timezone'),
            'order_hours' => $request->schedule(),
        ]);

        return redirect()->route('ordering.edit')->with('status', __('quick.hours.saved'));
    }

    /**
     * The one-tap "pause new orders" switch on the Today page.
     */
    public function pause(Request $request, CurrentShop $currentShop): JsonResponse
    {
        $paused = (bool) $request->validate(['orders_paused' => ['required', 'boolean']])['orders_paused'];

        $shop = $currentShop->get();
        $shop->update(['orders_paused' => $paused]);

        return response()->json([
            'orders_paused' => $paused,
            'closed' => $shop->orderingClosedReason(),
            'message' => __($paused ? 'quick.pause.on' : 'quick.pause.off'),
        ]);
    }
}
