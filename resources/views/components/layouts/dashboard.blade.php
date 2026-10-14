@props(['title' => null])
@php
    $shop = app(\App\Tenancy\CurrentShop::class)->get();

    // `match` decides which item is highlighted (e.g. products.edit → Products).
    $nav = [
        ['label' => __('app.nav.overview'), 'icon' => 'home', 'route' => 'dashboard', 'match' => 'dashboard'],
        ['label' => __('app.nav.categories'), 'icon' => 'grid', 'route' => 'categories.index', 'match' => 'categories.*'],
        ['label' => __('app.nav.products'), 'icon' => 'cup', 'route' => 'products.index', 'match' => 'products.*'],
        ['label' => __('analytics.nav'), 'icon' => 'chart', 'route' => 'analytics.index', 'match' => 'analytics.*'],
        ['label' => __('app.nav.reviews'), 'icon' => 'star', 'route' => 'reviews.index', 'match' => 'reviews.*'],
        ['label' => __('app.nav.qr'), 'icon' => 'qr', 'route' => 'qr.show', 'match' => 'qr.*'],
        ['label' => __('app.nav.settings'), 'icon' => 'settings', 'route' => 'settings.edit', 'match' => 'settings.*'],
    ];
@endphp
<x-layouts.base :title="$title ?? __('app.dashboard.title')">
    <x-toasts />

    {{-- Desktop sidebar ------------------------------------------------ --}}
    <aside class="vt-sidebar fixed inset-y-0 start-0 z-30 hidden w-72 flex-col border-e border-line bg-sand/70 px-5 py-6 lg:flex">
        <a href="{{ route('dashboard') }}" class="px-2"><x-logo /></a>

        <nav class="mt-10 space-y-1" aria-label="{{ __('app.nav.menu') }}">
            @foreach ($nav as $item)
                @php($active = request()->routeIs($item['match']))
                <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                   @class([
                       'group flex items-center gap-3 rounded-2xl px-3.5 py-3 font-medium transition-colors duration-200',
                       'bg-paper text-ink shadow-sm ring-1 ring-line' => $active,
                       'text-ink-soft hover:bg-paper/60 hover:text-ink' => ! $active,
                   ])>
                    <x-icon :name="$item['icon']" @class(['transition-transform duration-300 group-hover:scale-110', 'text-clay' => $active]) />
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="mt-auto space-y-4">
            <a href="{{ route('menu.show', $shop) }}" target="_blank" rel="noopener"
               class="group flex items-center gap-3 rounded-2xl bg-paper p-3 ring-1 ring-line transition hover:ring-clay/40"
               title="{{ __('app.nav.view_menu') }}">
                <x-shop-avatar :shop="$shop" />
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium rtl:text-right" dir="auto">{{ $shop->name }}</p>
                    <p class="truncate text-xs text-ink-mute rtl:text-right" dir="ltr">/{{ $shop->slug }}</p>
                </div>
                <x-icon name="external" class="size-4 text-ink-mute transition group-hover:text-clay" />
            </a>
            <div class="flex items-center justify-between gap-2">
                <a href="{{ route('account.edit') }}" class="grid size-10 place-items-center rounded-full text-ink-soft transition hover:bg-paper hover:text-clay"
                   title="{{ __('account.link') }}" aria-label="{{ __('account.link') }}"><x-icon name="user" /></a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="grid size-10 place-items-center rounded-full text-ink-soft transition hover:bg-paper hover:text-clay"
                            title="{{ __('app.nav.logout') }}" aria-label="{{ __('app.nav.logout') }}">
                        <x-icon name="logout" class="flip-rtl" />
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="lg:ps-72">
        {{-- Mobile top bar -------------------------------------------- --}}
        <header class="sticky top-0 z-20 flex items-center justify-between gap-3 border-b border-line/70 bg-paper/90 px-4 py-3 backdrop-blur-md lg:hidden">
            <div class="flex min-w-0 items-center gap-2.5">
                <x-shop-avatar :shop="$shop" size="size-9" />
                <p class="truncate font-medium rtl:text-right" dir="auto">{{ $shop->name }}</p>
            </div>
            <div class="flex items-center gap-1">
                <a href="{{ route('account.edit') }}" class="grid size-10 place-items-center rounded-full text-ink-soft active:bg-sand" aria-label="{{ __('account.link') }}"><x-icon name="user" /></a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="grid size-10 place-items-center rounded-full text-ink-soft active:bg-sand"
                            aria-label="{{ __('app.nav.logout') }}">
                        <x-icon name="logout" class="flip-rtl" />
                    </button>
                </form>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 pt-6 pb-32 sm:px-8 lg:px-12 lg:pt-12 lg:pb-16">
            <x-verify-banner />
            @unless (request()->routeIs('billing.*'))
                <x-subscription-notice :shop="$shop" class="mb-6" />
            @endunless
            {{ $slot }}
        </main>
    </div>

    {{-- Mobile bottom tab bar ------------------------------------------- --}}
    <nav class="vt-tabbar pb-safe fixed inset-x-0 bottom-0 z-30 border-t border-line bg-paper/95 px-2 pt-2 backdrop-blur-md lg:hidden"
         aria-label="{{ __('app.nav.menu') }}">
        <ul class="grid grid-cols-7">
            @foreach ($nav as $item)
                @php($active = request()->routeIs($item['match']))
                <li class="min-w-0">
                    <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                       @class(['flex flex-col items-center gap-1 rounded-2xl px-0.5 py-1.5 text-[10px] leading-tight font-medium transition active:scale-95',
                               'text-clay' => $active, 'text-ink-soft' => ! $active])>
                        <span @class(['grid h-8 w-12 place-items-center rounded-full transition-colors duration-300', 'bg-clay-soft' => $active])>
                            <x-icon :name="$item['icon']" />
                        </span>
                        <span class="max-w-full truncate">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</x-layouts.base>
