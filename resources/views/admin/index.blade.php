@php
    $statTiles = [
        ['key' => 'shops', 'icon' => 'home'],
        ['key' => 'new_week', 'icon' => 'sparkle'],
        ['key' => 'trial', 'icon' => 'star'],
        ['key' => 'due', 'icon' => 'tag'],
        ['key' => 'paid', 'icon' => 'check'],
        ['key' => 'with_whatsapp', 'icon' => 'whatsapp'],
        ['key' => 'products', 'icon' => 'cup'],
        ['key' => 'empty', 'icon' => 'grid'],
    ];
    $date = fn ($value) => $value->locale(app()->getLocale())->translatedFormat('j M Y');
@endphp
<x-layouts.admin>
    <x-page-header :title="__('app.admin.title')" :subtitle="__('app.admin.subtitle')">
        <x-slot:actions>
            <a href="{{ route('admin.shops.export', request()->only('q', 'status')) }}" class="btn btn-ghost">
                <x-icon name="external" class="size-4" /> {{ __('app.admin.export') }}
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ($statTiles as $i => $tile)
            <div class="reveal card p-4" style="--i: {{ $i + 1 }}">
                <span class="grid size-9 place-items-center rounded-full bg-sand text-clay"><x-icon :name="$tile['icon']" class="size-4" /></span>
                <p class="mt-3 font-display text-3xl font-semibold tabular-nums">{{ number_format($stats[$tile['key']]) }}</p>
                <p class="text-sm text-ink-soft">{{ __('app.admin.stats.'.$tile['key']) }}</p>
            </div>
        @endforeach
    </div>

    {{-- Search + filters --}}
    <div class="reveal mt-8 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between" style="--i: 7">
        <form method="GET" action="{{ route('admin.index') }}" class="relative w-full lg:max-w-md">
            <input type="hidden" name="status" value="{{ $filter }}">
            <label class="sr-only" for="q">{{ __('app.admin.search') }}</label>
            <x-icon name="search" class="pointer-events-none absolute start-4 top-1/2 size-5 -translate-y-1/2 text-ink-mute" />
            <input id="q" type="search" name="q" value="{{ $search }}" placeholder="{{ __('app.admin.search') }}" class="field ps-12">
        </form>
        <nav class="-mx-4 flex gap-2 overflow-x-auto px-4 [scrollbar-width:none] lg:mx-0 lg:px-0">
            @foreach (\App\Http\Controllers\Admin\ShopController::FILTERS as $option)
                <a href="{{ route('admin.index', array_filter(['q' => $search, 'status' => $option === 'all' ? null : $option])) }}"
                   @if ($filter === $option) aria-current="page" @endif
                   @class(['shrink-0 rounded-full px-4 py-2 text-sm font-medium transition-colors',
                           'bg-ink text-paper' => $filter === $option,
                           'bg-white/70 text-ink-soft ring-1 ring-line hover:text-ink' => $filter !== $option])>
                    {{ $option === 'expired' ? __('billing.states.expired') : __('app.admin.filters.'.$option) }}
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Shops --}}
    @if ($shops->isEmpty())
        <p class="card mt-6 px-6 py-14 text-center text-ink-soft">{{ __('app.admin.no_results') }}</p>
    @else
        <div class="reveal card mt-6 overflow-hidden" style="--i: 8">
            {{-- Column titles (desktop) --}}
            <div class="hidden grid-cols-[minmax(0,2.2fr)_minmax(0,2.2fr)_minmax(0,1.3fr)_5rem_9rem_6rem_2.5rem] gap-4 border-b border-line bg-sand/50 px-5 py-3 text-xs font-medium text-ink-mute lg:grid">
                <span>{{ __('app.admin.col_shop') }}</span>
                <span>{{ __('app.admin.col_owner') }}</span>
                <span>{{ __('app.admin.col_whatsapp') }}</span>
                <span>{{ __('app.admin.col_products') }}</span>
                <span>{{ __('app.admin.col_billing') }}</span>
                <span>{{ __('app.admin.col_status') }}</span>
                <span></span>
            </div>

            <ul class="divide-y divide-line/70">
                @foreach ($shops as $shop)
                    <li class="group relative grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 gap-y-2 px-4 py-4 transition-colors hover:bg-white/60 sm:px-5
                               lg:grid-cols-[minmax(0,2.2fr)_minmax(0,2.2fr)_minmax(0,1.3fr)_5rem_9rem_6rem_2.5rem] lg:items-center lg:gap-4">
                        {{-- Shop --}}
                        <div class="col-span-2 flex min-w-0 items-center gap-3 lg:col-span-1">
                            <x-shop-avatar :shop="$shop" />
                            <div class="min-w-0">
                                <a href="{{ route('admin.shops.show', $shop) }}" class="block truncate font-semibold after:absolute after:inset-0 lg:after:hidden rtl:text-right" dir="auto">{{ $shop->name }}</a>
                                <a href="{{ $shop->menuUrl() }}" target="_blank" rel="noopener" class="relative z-10 block truncate text-xs text-ink-mute hover:text-clay rtl:text-right" dir="ltr">/{{ $shop->slug }}</a>
                            </div>
                        </div>

                        {{-- Owner --}}
                        <div class="relative z-10 col-span-2 min-w-0 text-sm lg:col-span-1">
                            <p class="truncate rtl:text-right" dir="auto">{{ $shop->owner?->name }}</p>
                            @if ($shop->owner)
                                <a href="mailto:{{ $shop->owner->email }}" class="block truncate text-ink-soft hover:text-clay rtl:text-right" dir="ltr">{{ $shop->owner->email }}</a>
                            @endif
                        </div>

                        {{-- WhatsApp --}}
                        <div class="relative z-10 text-sm">
                            @if ($shop->whatsapp_number)
                                <a href="https://wa.me/{{ $shop->whatsapp_number }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1.5 text-olive hover:underline" dir="ltr">
                                    <x-icon name="whatsapp" class="size-4" /> +{{ $shop->whatsapp_number }}
                                </a>
                            @else
                                <span class="text-ink-mute">{{ __('app.admin.not_set') }}</span>
                            @endif
                        </div>

                        {{-- Products · joined · status (one line on mobile) --}}
                        <div class="flex flex-wrap items-center justify-end gap-x-3 gap-y-1 text-sm lg:contents">
                            <span class="tabular-nums lg:block"><span class="text-ink-mute lg:hidden">{{ __('app.admin.col_products') }}: </span>{{ $shop->products_count }}</span>
                            <x-billing-pill :shop="$shop" class="lg:flex" />
                            <span class="lg:block">
                                <span @class(['inline-block rounded-full px-2.5 py-0.5 text-xs font-medium',
                                              'bg-olive-soft text-olive' => $shop->is_active,
                                              'bg-danger/10 text-danger' => ! $shop->is_active])>
                                    {{ $shop->is_active ? __('app.admin.status_active') : __('app.admin.status_suspended') }}
                                </span>
                            </span>
                        </div>

                        <a href="{{ route('admin.shops.show', $shop) }}" class="hidden size-9 place-items-center rounded-full text-ink-mute transition hover:bg-sand hover:text-clay lg:grid"
                           aria-label="{{ __('app.admin.details') }}">
                            <x-icon name="arrow" class="flip-rtl size-4" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Pagination --}}
        @if ($shops->lastPage() > 1)
            <nav class="mt-6 flex items-center justify-between gap-3 text-sm">
                <a href="{{ $shops->previousPageUrl() ?? '#' }}" @class(['btn btn-ghost px-4 py-2', 'pointer-events-none opacity-40' => $shops->onFirstPage()])>
                    <x-icon name="arrow" class="flip-rtl size-4 rotate-180" /> {{ __('app.admin.previous') }}
                </a>
                <span class="text-ink-soft">{{ __('app.admin.page', ['current' => $shops->currentPage(), 'last' => $shops->lastPage()]) }}</span>
                <a href="{{ $shops->nextPageUrl() ?? '#' }}" @class(['btn btn-ghost px-4 py-2', 'pointer-events-none opacity-40' => ! $shops->hasMorePages()])>
                    {{ __('app.admin.next') }} <x-icon name="arrow" class="flip-rtl size-4" />
                </a>
            </nav>
        @endif
    @endif
</x-layouts.admin>
