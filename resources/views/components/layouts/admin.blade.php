{{-- Platform admin shell: a dark espresso bar so it never feels like a shop's dashboard. --}}
@props(['title' => null])
<x-layouts.base :title="$title ?? __('app.admin.title')">
    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>

    <x-toasts />

    <header class="vt-sidebar sticky top-0 z-30 bg-ink text-paper">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-8">
            <a href="{{ route('admin.index') }}" class="flex items-center gap-3">
                <x-logo tone="paper" />
                <span class="rounded-full bg-saffron px-2.5 py-0.5 text-xs font-semibold text-ink">{{ __('app.admin.badge') }}</span>
            </a>

            <div class="flex items-center gap-1.5">
                @if (auth()->user()->shop_id)
                    <a href="{{ route('dashboard') }}" class="hidden rounded-full px-3 py-2 text-sm text-paper/75 transition hover:bg-paper/10 hover:text-paper sm:block">
                        {{ __('app.admin.my_shop') }}
                    </a>
                @endif
                <x-locale-switcher tone="dark" compact />
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="grid size-10 place-items-center rounded-full text-paper/75 transition hover:bg-paper/10 hover:text-paper"
                            title="{{ __('app.nav.logout') }}" aria-label="{{ __('app.nav.logout') }}">
                        <x-icon name="logout" class="flip-rtl" />
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 pt-8 pb-20 sm:px-8 lg:pt-12">
        {{ $slot }}
    </main>
</x-layouts.base>
