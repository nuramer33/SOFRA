@php
    $owner = $shop->users->first();
    $date = fn ($value) => $value?->locale(app()->getLocale())->translatedFormat('j M Y');
    $dateTime = fn ($value) => $value?->locale(app()->getLocale())->translatedFormat('j M Y · H:i');
    $details = [
        __('app.fields.currency') => $shop->currency.' · '.\App\Support\Money::symbol($shop->currency),
        __('app.fields.languages') => collect($shop->contentLocales())->map(fn ($l) => \App\Support\Locales::name($l))->implode('، '),
        __('app.fields.order_types') => collect($shop->orderTypes())->map(fn ($t) => __('app.cart.types.'.$t))->implode('، '),
        __('app.admin.qr_downloaded') => $shop->qr_downloaded_at ? $date($shop->qr_downloaded_at) : __('app.admin.no'),
        __('app.admin.col_joined') => $date($shop->created_at),
    ];
@endphp
<x-layouts.admin :title="$shop->name">
    <x-page-header :title="$shop->name" :back="route('admin.index')">
        <x-slot:actions>
            <span @class(['rounded-full px-3 py-1 text-sm font-medium',
                          'bg-olive-soft text-olive' => $shop->is_active,
                          'bg-danger/10 text-danger' => ! $shop->is_active])>
                {{ $shop->is_active ? __('app.admin.status_active') : __('app.admin.status_suspended') }}
            </span>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-3 lg:gap-6">
        {{-- Identity --}}
        <section class="reveal card p-6 lg:col-span-2" style="--i: 1">
            <div class="flex items-center gap-4">
                <x-shop-avatar :shop="$shop" size="size-16" />
                <div class="min-w-0">
                    @foreach ($shop->getTranslations('name') as $locale => $name)
                        <p @class(['truncate', 'font-display text-2xl font-semibold' => $loop->first, 'text-sm text-ink-soft' => ! $loop->first])
                           lang="{{ $locale }}" dir="{{ \App\Support\Locales::dir($locale) }}">{{ $name }}</p>
                    @endforeach
                </div>
            </div>
            @if ($shop->tagline)
                <p class="mt-4 text-ink-soft">{{ $shop->tagline }}</p>
            @endif

            <div class="mt-6 flex flex-wrap gap-2">
                <a href="{{ $shop->menuUrl() }}" target="_blank" rel="noopener" class="btn btn-ghost px-4 py-2.5 text-sm">
                    <x-icon name="external" class="size-4" /> {{ __('app.nav.view_menu') }}
                </a>
                @if ($shop->whatsapp_number)
                    <a href="https://wa.me/{{ $shop->whatsapp_number }}" target="_blank" rel="noopener" class="btn btn-ghost px-4 py-2.5 text-sm text-olive">
                        <x-icon name="whatsapp" class="size-4" /> {{ __('app.admin.message_whatsapp') }}
                    </a>
                @endif
                @if ($owner)
                    <a href="mailto:{{ $owner->email }}" class="btn btn-ghost px-4 py-2.5 text-sm">
                        <x-icon name="user" class="size-4" /> {{ __('app.admin.send_email') }}
                    </a>
                @endif
            </div>

            <dl class="mt-6 grid gap-x-6 gap-y-4 border-t border-line pt-6 sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-ink-mute">{{ __('app.admin.menu_link') }}</dt>
                    <dd class="mt-0.5 truncate rtl:text-right" dir="ltr">{{ $shop->menuUrl() }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-mute">{{ __('app.fields.whatsapp') }}</dt>
                    <dd class="mt-0.5 rtl:text-right" dir="ltr">{{ $shop->whatsapp_number ? '+'.$shop->whatsapp_number : __('app.admin.not_set') }}</dd>
                </div>
                @foreach ($details as $label => $value)
                    <div>
                        <dt class="text-xs text-ink-mute">{{ $label }}</dt>
                        <dd class="mt-0.5">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <div class="space-y-4 lg:space-y-6">
            {{-- Subscription --}}
            <section class="reveal card p-6" style="--i: 2">
                <h2 class="flex items-center justify-between gap-3 font-display text-xl font-semibold">
                    {{ __('app.admin.billing') }}
                    <x-billing-pill :shop="$shop" :detailed="false" />
                </h2>
                <dl class="mt-4 space-y-2.5 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-ink-mute">{{ __('app.admin.registered') }}</dt><dd>{{ $dateTime($shop->created_at) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-ink-mute">{{ __('app.admin.trial_ends') }}</dt><dd>{{ $date($shop->trial_ends_at) ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-ink-mute">{{ __('app.admin.paid_until') }}</dt><dd>{{ $date($shop->paid_until) ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-ink-mute">{{ __('billing.admin.auto_renew') }}</dt><dd>{{ $shop->hasAutoRenew() ? __('billing.admin.yes') : __('billing.admin.no') }}@if ($shop->payment_failed_at) <span class="text-danger">· {{ __('billing.admin.events.payment_failed') }}</span>@endif</dd></div>
                </dl>

                <form method="POST" action="{{ route('admin.shops.payments', $shop) }}" class="mt-5 border-t border-line pt-5">
                    @csrf
                    <label for="months" class="text-sm font-medium text-ink-soft">{{ __('app.admin.record_payment') }}</label>
                    <div class="mt-2 flex gap-2">
                        <div class="relative flex-1">
                            <select id="months" name="months" class="field appearance-none py-2.5 pe-10">
                                @foreach (\App\Http\Controllers\Admin\ShopPaymentController::MONTH_OPTIONS as $months)
                                    <option value="{{ $months }}">{{ trans_choice('app.admin.months', $months, ['count' => $months]) }}</option>
                                @endforeach
                            </select>
                            <x-icon name="chevron-down" class="pointer-events-none absolute end-4 top-1/2 size-4 -translate-y-1/2 text-ink-mute" />
                        </div>
                        <button class="btn btn-primary px-5 py-2.5">{{ __('app.actions.save') }}</button>
                    </div>
                    <p class="mt-2 text-xs text-ink-mute">{{ __('app.admin.billing_hint') }}</p>
                </form>

                <form method="POST" action="{{ route('admin.shops.extensions', $shop) }}" class="mt-5 border-t border-line pt-5">
                    @csrf
                    <label for="days" class="text-sm font-medium text-ink-soft">{{ __('billing.admin.extend') }}</label>
                    <div class="mt-2 flex gap-2">
                        <div class="relative flex-1">
                            <select id="days" name="days" class="field appearance-none py-2.5 pe-10">
                                @foreach (\App\Http\Controllers\Admin\ShopExtensionController::DAY_OPTIONS as $days)
                                    <option value="{{ $days }}">{{ __('billing.admin.days', ['count' => $days]) }}</option>
                                @endforeach
                            </select>
                            <x-icon name="chevron-down" class="pointer-events-none absolute end-4 top-1/2 size-4 -translate-y-1/2 text-ink-mute" />
                        </div>
                        <button class="btn btn-ghost px-5 py-2.5">{{ __('billing.admin.extend_button') }}</button>
                    </div>
                    <p class="mt-2 text-xs text-ink-mute">{{ __('billing.admin.extend_hint') }}</p>
                </form>

                @if ($shop->subscriptionEvents->isNotEmpty())
                    <div class="mt-5 border-t border-line pt-5">
                        <h3 class="text-sm font-medium text-ink-soft">{{ __('billing.admin.history') }}</h3>
                        <ul class="mt-2 space-y-1.5 text-xs text-ink-soft">
                            @foreach ($shop->subscriptionEvents as $event)
                                <li class="flex justify-between gap-3">
                                    <span>{{ __('billing.admin.events.'.$event->type, ['amount' => $event->amount]) }}@if ($event->user) · <bdi>{{ $event->user->name }}</bdi>@endif</span>
                                    <time class="shrink-0 text-ink-mute" dir="ltr" datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->format('Y-m-d') }}</time>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>

            {{-- Accounts --}}
            <section class="reveal card p-6" style="--i: 2">
                <h2 class="font-display text-xl font-semibold">{{ __('app.admin.accounts') }}</h2>
                @if (session('temporary_password'))
                    <div class="mt-4 rounded-2xl bg-saffron/20 p-4 text-sm">
                        <p>{{ session('status') }}</p>
                        <p class="mt-2 select-all font-mono text-lg font-semibold" dir="ltr">{{ session('temporary_password') }}</p>
                        <p class="mt-1 text-xs text-ink-mute">{{ __('billing.admin.password_once') }}</p>
                    </div>
                @endif
                <ul class="mt-4 space-y-4">
                    @foreach ($shop->users as $user)
                        <li class="flex items-start gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-sand text-ink-soft"><x-icon name="user" class="size-4" /></span>
                            <div class="min-w-0">
                                <p class="flex items-center gap-2 font-medium" dir="auto">
                                    {{ $user->name }}
                                    @if ($user->isAdmin())
                                        <span class="rounded-full bg-saffron/30 px-2 text-[11px] text-clay-deep">{{ __('app.admin.admin_badge') }}</span>
                                    @endif
                                </p>
                                <a href="mailto:{{ $user->email }}" class="block truncate text-sm text-ink-soft hover:text-clay rtl:text-right" dir="ltr">{{ $user->email }}</a>
                                <p class="text-xs text-ink-mute">{{ __('app.admin.registered') }}: {{ $dateTime($user->created_at) }}</p>
                                <p class="text-xs text-ink-mute">{{ __('app.admin.last_login') }}: {{ $user->last_login_at ? $dateTime($user->last_login_at) : __('app.admin.never') }}</p>
                                @if (! $user->isAdmin())
                                    <form method="POST" action="{{ route('admin.shops.users.password', [$shop, $user]) }}" class="mt-2" onsubmit="return confirm('{{ addslashes(__('billing.admin.password_confirm')) }}')">
                                        @csrf
                                        <button class="text-xs font-medium text-clay hover:underline">{{ __('billing.admin.password_button') }}</button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- Categories --}}
            <section class="reveal card p-6" style="--i: 3">
                <h2 class="flex items-baseline justify-between font-display text-xl font-semibold">
                    {{ __('app.admin.categories') }}
                    <span class="font-sans text-sm font-normal text-ink-mute">{{ __('app.admin.col_products') }}: {{ $shop->products_count }}</span>
                </h2>
                <ul class="mt-3 divide-y divide-line/70 text-sm">
                    @forelse ($shop->categories as $category)
                        <li class="flex items-center justify-between py-2.5">
                            <span @class(['text-ink-mute' => ! $category->is_active])>{{ $category->name }}</span>
                            <span class="tabular-nums text-ink-soft">{{ $category->products_count }}</span>
                        </li>
                    @empty
                        <li class="py-2.5 text-ink-mute">—</li>
                    @endforelse
                </ul>
            </section>

            {{-- Suspend / activate --}}
            <section class="reveal" style="--i: 4">
                @if ($shop->is_active)
                    <x-delete-dialog :action="route('admin.shops.status', $shop)" method="PATCH"
                                     :title="__('app.admin.suspend_title', ['name' => $shop->name])"
                                     :text="__('app.admin.suspend_text')" :confirm="__('app.admin.suspend')"
                                     class="w-full border border-danger/30">
                        <x-slot:trigger>{{ __('app.admin.suspend') }}</x-slot:trigger>
                    </x-delete-dialog>
                @else
                    <x-delete-dialog :action="route('admin.shops.status', $shop)" method="PATCH" :danger="false"
                                     :title="__('app.admin.activate_title', ['name' => $shop->name])"
                                     :text="__('app.admin.activate_text')" :confirm="__('app.admin.activate')"
                                     class="w-full">
                        <x-slot:trigger>{{ __('app.admin.activate') }}</x-slot:trigger>
                    </x-delete-dialog>
                @endif
            </section>
        </div>
    </div>
</x-layouts.admin>
