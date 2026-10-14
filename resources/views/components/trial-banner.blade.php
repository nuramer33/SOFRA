{{-- Owner-facing subscription status on the overview: free days left, or a clear renewal notice. --}}
@props(['shop'])
@php
    $status = $shop->billingStatus();
    $date = fn ($value) => $value?->locale(app()->getLocale())->translatedFormat('j F Y');

    $totalDays = max(1, (int) round($shop->created_at->diffInDays($shop->trial_ends_at ?? $shop->created_at)));
    $progress = min(100, max(4, (int) round(100 * ($totalDays - $shop->trialDaysLeft()) / $totalDays)));
@endphp
@if ($status === \App\Models\Shop::BILLING_PAID)
    <p {{ $attributes->class('reveal flex flex-wrap items-center gap-x-2 text-sm text-olive') }}>
        <x-icon name="check" class="size-4" /> {{ __('app.billing.paid', ['date' => $date($shop->paid_until)]) }}
        <a href="{{ route('billing.show') }}" class="text-ink-soft underline-offset-4 hover:underline">{{ __('billing.details') }}</a>
    </p>
@elseif (in_array($status, [\App\Models\Shop::BILLING_PAST_DUE, \App\Models\Shop::BILLING_EXPIRED], true))
    {{-- The renewal notice is shown above the page content by <x-subscription-notice>. --}}
@else
    <section {{ $attributes->class('reveal card p-5 sm:p-6') }}>
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="flex items-center gap-2 font-display text-lg font-semibold">
                <x-icon name="sparkle" class="size-4 text-saffron" /> {{ __('app.billing.trial_title') }}
            </h2>
            <p @class(['text-sm', 'font-medium text-clay' => $status === \App\Models\Shop::BILLING_TRIAL_ENDING, 'text-ink-soft' => $status !== \App\Models\Shop::BILLING_TRIAL_ENDING])>
                {{ __('app.billing.days_left', ['days' => $shop->trialDaysLeft()]) }} · {{ __('app.billing.ends', ['date' => $date($shop->trial_ends_at)]) }}
            </p>
        </div>
        <div class="mt-4 h-2 overflow-hidden rounded-full bg-sand" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
            <div x-data="{ on: false }" x-init="requestAnimationFrame(() => on = true)"
                 class="h-full rounded-full bg-linear-to-l from-clay to-saffron transition-[width] duration-[1400ms] ease-(--ease-soft) ltr:bg-linear-to-r"
                 :style="{ width: on ? '{{ $progress }}%' : '0%' }" style="width: 0%"></div>
        </div>
        <p class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-ink-mute">
            <span>{{ __('app.billing.trial_note') }}</span>
            <a href="{{ route('billing.show') }}" class="text-clay underline-offset-4 hover:underline">{{ __('billing.details') }}</a>
        </p>
    </section>
@endif
