@php
    $date = fn ($value) => $value?->locale(app()->getLocale())->translatedFormat('j F Y');
    $expired = $status === \App\Models\Shop::BILLING_EXPIRED;
    $timeline = [
        ['label' => __('billing.page.registered'), 'value' => $date($shop->created_at)],
        ['label' => __('billing.page.trial_ends'), 'value' => $date($shop->trial_ends_at) ?? '—'],
        ['label' => __('billing.page.paid_until'), 'value' => $date($shop->paid_until) ?? '—'],
        ['label' => __('billing.page.due_on'), 'value' => $date($dueAt) ?? '—'],
    ];
@endphp
<x-layouts.dashboard :title="__('billing.page.title')">
    <x-page-header :title="__('billing.page.title')" :subtitle="__('billing.page.subtitle')" />

    <div class="grid gap-4 lg:grid-cols-3 lg:gap-6">
        {{-- Where you stand --}}
        <section class="reveal card p-6 lg:col-span-2" style="--i: 1">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-display text-xl font-semibold">{{ __('billing.page.status') }}</h2>
                <x-billing-pill :shop="$shop" :detailed="false" class="scale-110" />
            </div>

            <p class="mt-3 text-ink-soft">{{ __('billing.page.explain.'.$status, ['date' => $date($dueAt), 'grace' => $date($graceEndsAt), 'days' => $shop->daysUntilDue()]) }}</p>

            <dl class="mt-6 grid gap-x-6 gap-y-4 border-t border-line pt-6 sm:grid-cols-2">
                @foreach ($timeline as $row)
                    <div>
                        <dt class="text-xs text-ink-mute">{{ $row['label'] }}</dt>
                        <dd class="mt-0.5 font-medium">{{ $row['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- How to renew --}}
        <section class="reveal card flex flex-col p-6" style="--i: 2">
            <h2 class="font-display text-xl font-semibold">{{ __('billing.page.renew_title') }}</h2>
            <p class="mt-3 font-display text-4xl font-semibold" dir="ltr">{{ $price }}<span class="text-base font-normal text-ink-mute"> / {{ __('billing.page.month') }}</span></p>

            @if ($stripeEnabled)
                @if ($checkout === 'success')
                    <p role="status" class="mt-3 rounded-2xl bg-olive-soft p-3 text-sm text-olive">{{ __('billing.pay.success') }}</p>
                @elseif ($checkout === 'cancelled')
                    <p role="status" class="mt-3 rounded-2xl bg-sand p-3 text-sm text-ink-soft">{{ __('billing.pay.cancelled') }}</p>
                @endif

                @if ($shop->payment_failed_at)
                    <p role="alert" class="mt-3 rounded-2xl bg-danger/10 p-3 text-sm text-danger">{{ __('billing.pay.failed_card') }}</p>
                @endif

                @if ($shop->hasAutoRenew())
                    <p class="mt-3 flex items-start gap-2 text-sm text-olive"><x-icon name="check" class="mt-0.5 size-4 shrink-0" /> {{ __('billing.pay.auto_on', ['date' => $date($dueAt)]) }}</p>
                    <form method="POST" action="{{ route('billing.portal') }}" class="mt-5">
                        @csrf
                        <button class="btn btn-ghost w-full">{{ __('billing.pay.manage') }}</button>
                    </form>
                    <p class="mt-2 text-xs text-ink-mute">{{ __('billing.pay.manage_hint') }}</p>
                @else
                    <p class="mt-2 text-sm text-ink-soft">{{ $shop->stripe_customer_id ? __('billing.pay.ended') : __('billing.pay.text') }}</p>
                    <form method="POST" action="{{ route('billing.checkout') }}" class="mt-5">
                        @csrf
                        <button class="btn btn-primary w-full">{{ $shop->stripe_customer_id ? __('billing.pay.again') : __('billing.pay.subscribe') }}</button>
                    </form>
                    <p class="mt-2 text-xs text-ink-mute">{{ __('billing.pay.secure') }}</p>
                    @if ($shop->stripe_customer_id)
                        <form method="POST" action="{{ route('billing.portal') }}" class="mt-2">
                            @csrf
                            <button class="text-xs text-ink-soft underline-offset-4 hover:underline">{{ __('billing.pay.manage') }}</button>
                        </form>
                    @endif
                @endif

                @if ($contact)
                    <a href="{{ $contact }}" target="_blank" rel="noopener" class="mt-5 text-sm text-ink-soft underline-offset-4 hover:underline">{{ __('billing.pay.other_way') }}</a>
                @endif
            @else
                <p class="mt-2 text-sm text-ink-soft">{{ __('billing.page.renew_text') }}</p>
                @if ($contact)
                    <a href="{{ $contact }}" target="_blank" rel="noopener" class="btn btn-primary mt-5">{{ __('billing.page.contact') }}</a>
                @else
                    <p class="mt-5 rounded-2xl bg-sand p-3 text-sm text-ink-soft">{{ __('billing.page.no_contact') }}</p>
                @endif
            @endif
        </section>

        {{-- What happens when it ends --}}
        <section class="reveal card p-6 lg:col-span-3" style="--i: 3">
            <h2 class="font-display text-xl font-semibold">{{ __('billing.page.after_title') }}</h2>
            <ul class="mt-3 space-y-2 text-sm text-ink-soft">
                <li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-olive" /> {{ __('billing.page.after_grace', ['days' => config('sofra.billing.grace_days')]) }}</li>
                <li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-olive" /> {{ __('billing.page.after_menu.'.$expiredMode) }}</li>
                <li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-olive" /> {{ __('billing.page.after_data') }}</li>
            </ul>
        </section>
    </div>
</x-layouts.dashboard>
