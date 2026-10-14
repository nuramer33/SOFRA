{{--
    A clear notice when the subscription needs attention: the due date is close
    (7 days or less), it is past due (grace period) or it has expired.
    Silent in every other state, so it never nags a healthy shop.
--}}
@props(['shop'])
@php
    $status = $shop->billingStatus();
    $days = $shop->daysUntilDue();
    // On the overview the free-period card already says "ending soon".
    $endingStates = request()->routeIs('dashboard')
        ? [\App\Models\Shop::BILLING_PAID]
        : [\App\Models\Shop::BILLING_PAID, \App\Models\Shop::BILLING_TRIAL_ENDING];
    $ending = in_array($status, $endingStates, true)
        && $days !== null && $days <= config('sofra.billing.ending_soon_days');
    $overdue = in_array($status, [\App\Models\Shop::BILLING_PAST_DUE, \App\Models\Shop::BILLING_EXPIRED], true);
    $date = fn ($value) => $value?->locale(app()->getLocale())->translatedFormat('j F Y');
@endphp
@if ($ending || $overdue)
    <section {{ $attributes->class([
        'reveal flex flex-col gap-3 rounded-3xl p-5 ring-1 sm:flex-row sm:items-center',
        'bg-saffron/15 ring-saffron/40' => $ending || $status === \App\Models\Shop::BILLING_PAST_DUE,
        'bg-clay-soft ring-clay/20' => $status === \App\Models\Shop::BILLING_EXPIRED,
    ]) }} role="status">
        <div class="flex-1">
            <h2 class="font-display text-lg font-semibold text-clay-deep">
                @if ($status === \App\Models\Shop::BILLING_EXPIRED) {{ __('billing.notice.expired_title') }}
                @elseif ($status === \App\Models\Shop::BILLING_PAST_DUE) {{ __('billing.notice.past_due_title') }}
                @else {{ __('billing.notice.ending_title', ['days' => max($days, 0)]) }}
                @endif
            </h2>
            <p class="mt-1 text-sm text-ink-soft">
                @if ($status === \App\Models\Shop::BILLING_EXPIRED) {{ __('billing.notice.expired_text.'.\App\Models\Shop::expiredMenuMode()) }}
                @elseif ($status === \App\Models\Shop::BILLING_PAST_DUE) {{ __('billing.notice.past_due_text', ['date' => $date($shop->paymentDueAt()->copy()->addDays(config('sofra.billing.grace_days')))]) }}
                @else {{ __('billing.notice.ending_text', ['date' => $date($shop->paymentDueAt())]) }}
                @endif
            </p>
        </div>
        <a href="{{ route('billing.show') }}" class="btn btn-primary shrink-0">{{ __('billing.notice.cta') }}</a>
    </section>
@endif
