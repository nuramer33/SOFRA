{{-- Why ordering is closed (subscription, paused, outside hours). Shared by every menu template. --}}
        @php
            $closedReason = $shop->canReceiveOrders() ? $shop->orderingClosedReason() : null;
            $opensAt = $closedReason === 'hours' ? $shop->nextOrderingOpening() : null;
        @endphp
        @if ($shop->ordersPausedBySubscription())
            <p role="status" class="mx-auto mt-4 max-w-2xl rounded-2xl bg-saffron/20 px-4 py-3 text-center text-sm text-clay-deep">{{ __('billing.public.paused') }}</p>
        @elseif ($closedReason)
            <p role="status" class="mx-auto mt-4 max-w-2xl rounded-2xl bg-saffron/20 px-4 py-3 text-center text-sm text-clay-deep">
                {{ __("quick.closed.{$closedReason}") }}
                @if ($opensAt)
                    <span class="block text-xs text-ink-soft">{{ $opensAt->isToday()
                        ? __('quick.opens_today', ['time' => $opensAt->format('H:i')])
                        : __('quick.opens_on', ['day' => $opensAt->locale(app()->getLocale())->translatedFormat('l'), 'time' => $opensAt->format('H:i')]) }}</span>
                @endif
            </p>
        @endif
