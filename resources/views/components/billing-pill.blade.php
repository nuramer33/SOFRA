{{-- Subscription state of a shop + the date that matters for it. --}}
@props(['shop', 'detailed' => true])
@php
    $status = $shop->billingStatus();
    $date = fn ($value) => $value?->locale(app()->getLocale())->translatedFormat('j M Y');
    $sub = match ($status) {
        \App\Models\Shop::BILLING_PAID => __('app.admin.until', ['date' => $date($shop->paid_until)]),
        \App\Models\Shop::BILLING_PAST_DUE, \App\Models\Shop::BILLING_EXPIRED => __('app.admin.since', ['date' => $date($shop->paymentDueAt())]),
        default => __('app.admin.days_left', ['days' => $shop->trialDaysLeft()]),
    };
    $tone = match ($status) {
        \App\Models\Shop::BILLING_PAID => ['bg-olive-soft text-olive', 'bg-olive'],
        \App\Models\Shop::BILLING_TRIAL => ['bg-sand text-ink-soft', 'bg-ink-mute'],
        \App\Models\Shop::BILLING_TRIAL_ENDING, \App\Models\Shop::BILLING_PAST_DUE => ['bg-saffron/25 text-clay-deep', 'bg-saffron'],
        default => ['bg-danger/10 text-danger', 'bg-danger'],
    };
@endphp
<span {{ $attributes->class('inline-flex flex-col items-start gap-0.5') }}>
    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $tone[0] }}">
        <span class="size-1.5 rounded-full {{ $tone[1] }}"></span>
        {{ __('billing.states.'.$status) }}
    </span>
    @if ($detailed)
        <span class="text-[11px] text-ink-mute">{{ $sub }}</span>
    @endif
</span>
