@php
    $done = count(array_filter($steps));
    $total = count($steps);
    $circumference = 2 * M_PI * 26;
@endphp
<x-layouts.dashboard>
    {{-- Greeting --}}
    <header class="reveal">
        <p class="eyebrow">{{ now()->locale(app()->getLocale())->translatedFormat(app()->getLocale() === 'ar' ? 'l، j F' : 'l, j F') }}</p>
        <h1 class="mt-2 font-display text-4xl font-semibold sm:text-5xl">
            {{ __('app.dashboard.greeting', ['name' => Str::before(auth()->user()->name, ' ')]) }}
        </h1>
        <p class="mt-2 text-ink-soft">{{ __('app.dashboard.subtitle') }}</p>
    </header>

    <x-trial-banner :shop="$shop" class="mt-6" />

    <a href="{{ route('today') }}" class="reveal card mt-6 flex items-center justify-between gap-4 p-5 transition hover:ring-clay/40 sm:p-6" style="--i: 1">
        <span class="flex items-center gap-4">
            <span class="grid size-12 shrink-0 place-items-center rounded-full bg-olive-soft text-olive"><x-icon name="clock" class="size-6" /></span>
            <span>
                <span class="block font-display text-xl font-semibold">{{ __('quick.today.title') }}</span>
                <span class="block text-sm text-ink-soft">{{ __('quick.today.subtitle') }}</span>
            </span>
        </span>
        <x-icon name="arrow" class="flip-rtl size-5 shrink-0 text-clay" />
    </a>

    <div class="mt-6 grid gap-4 lg:grid-cols-3 lg:gap-6">
        {{-- Menu link --}}
        <section class="reveal relative overflow-hidden rounded-3xl bg-ink p-6 text-paper sm:p-8 lg:col-span-2" style="--i: 1"
                 x-data="copyable(@js($menuUrl))">
            <div class="arch pointer-events-none absolute -bottom-24 end-6 h-56 w-40 border border-paper/10" aria-hidden="true"></div>
            <div class="arch pointer-events-none absolute -bottom-28 end-14 h-40 w-24 bg-clay/40" aria-hidden="true"></div>

            <div class="relative">
                <p class="flex items-center gap-2 text-sm text-paper/70"><x-icon name="link" class="size-4" /> {{ __('app.dashboard.menu_link') }}</p>
                <p class="mt-3 truncate font-display text-2xl sm:text-3xl rtl:text-right" dir="ltr">
                    <span class="text-paper/50">{{ parse_url($menuUrl, PHP_URL_HOST) }}/</span>{{ $shop->slug }}
                </p>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <button type="button" @click="copy" class="btn bg-paper px-5 py-3 text-ink hover:bg-white">
                        <span class="relative grid size-4 place-items-center">
                            <x-icon name="copy" class="absolute size-4 transition duration-300" x-bind:class="copied && 'scale-0 opacity-0'" />
                            <x-icon name="check" class="absolute size-4 scale-0 text-olive opacity-0 transition duration-300 ease-(--ease-spring)" x-bind:class="copied && '!scale-100 !opacity-100'" />
                        </span>
                        <span x-text="copied ? @js(__('app.dashboard.copied')) : @js(__('app.dashboard.copy'))">{{ __('app.dashboard.copy') }}</span>
                    </button>
                    <a href="{{ $menuUrl }}" target="_blank" rel="noopener" class="btn border border-paper/25 px-5 py-3 text-paper hover:bg-paper/10">
                        <x-icon name="external" class="size-4" /> {{ __('app.nav.view_menu') }}
                    </a>
                </div>
                <p class="mt-4 text-xs text-paper/60">{{ __('app.dashboard.link_note') }}</p>
            </div>
        </section>

        {{-- Stats --}}
        <section class="reveal card grid grid-cols-3 divide-x divide-line p-2 lg:grid-cols-1 lg:divide-x-0 lg:divide-y" style="--i: 2">
            @foreach ([
                ['value' => $categoryCount, 'label' => __('app.dashboard.stats.categories'), 'icon' => 'grid'],
                ['value' => $productCount, 'label' => __('app.dashboard.stats.products'), 'icon' => 'cup'],
                ['value' => count($shop->enabled_locales ?? []), 'label' => __('app.dashboard.stats.languages'), 'icon' => 'globe'],
            ] as $stat)
                <div class="flex flex-col items-center gap-1 px-3 py-4 text-center lg:flex-row lg:gap-4 lg:px-5 lg:text-start">
                    <span class="hidden size-10 place-items-center rounded-full bg-sand text-clay lg:grid"><x-icon :name="$stat['icon']" /></span>
                    <span class="font-display text-3xl font-semibold">{{ $stat['value'] }}</span>
                    <span class="text-sm text-ink-soft">{{ $stat['label'] }}</span>
                </div>
            @endforeach
        </section>
    </div>

    {{-- Setup checklist --}}
    <section class="reveal card mt-4 p-6 sm:p-8 lg:mt-6" style="--i: 3">
        <div class="flex items-center gap-5">
            <div class="relative size-16 shrink-0" x-data="{ on: false }" x-init="requestAnimationFrame(() => on = true)">
                <svg viewBox="0 0 64 64" class="size-16 -rotate-90 rtl:-scale-y-100" aria-hidden="true">
                    <circle cx="32" cy="32" r="26" fill="none" stroke-width="6" class="stroke-sand-deep" />
                    <circle cx="32" cy="32" r="26" fill="none" stroke-width="6" stroke-linecap="round" class="stroke-clay transition-[stroke-dashoffset] duration-[1400ms] ease-(--ease-soft)"
                            stroke-dasharray="{{ $circumference }}"
                            stroke-dashoffset="{{ $circumference }}"
                            x-bind:stroke-dashoffset="on ? {{ $circumference * (1 - $done / $total) }} : {{ $circumference }}" />
                </svg>
                <span class="absolute inset-0 grid place-items-center text-sm font-semibold">{{ $done }}/{{ $total }}</span>
            </div>
            <div>
                <h2 class="font-display text-2xl font-semibold">{{ __('app.dashboard.setup_title') }}</h2>
                <p class="text-sm text-ink-soft">{{ __('app.dashboard.setup_progress', ['done' => $done, 'total' => $total]) }}</p>
            </div>
        </div>

        @php($links = [
            'identity' => route('settings.edit'),
            'whatsapp' => route('settings.edit').'#orders',
            'category' => route('categories.create'),
            'product' => route('products.create'),
            'share' => route('qr.show'),
        ])
        <ol class="mt-7 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($steps as $key => $isDone)
                @php($link = $isDone ? null : ($links[$key] ?? null))
                <li>
                    <{{ $link ? 'a' : 'div' }} @if ($link) href="{{ $link }}" @endif
                        @class(['group flex h-full items-start gap-3.5 rounded-2xl p-4 ring-1 transition duration-300',
                                'bg-olive-soft/60 ring-olive/20' => $isDone,
                                'bg-paper/70 ring-line' => ! $isDone,
                                'hover:-translate-y-0.5 hover:bg-white hover:shadow-md hover:ring-clay/30' => $link])>
                        <span @class(['grid size-8 shrink-0 place-items-center rounded-full text-sm font-semibold transition-colors',
                                      'bg-olive text-paper' => $isDone,
                                      'bg-sand text-ink-soft group-hover:bg-clay group-hover:text-paper' => ! $isDone])>
                            @if ($isDone) <x-icon name="check" class="size-4" /> @else {{ $loop->iteration }} @endif
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2 font-medium">
                                {{ __("app.dashboard.steps.$key.title") }}
                                @if (! $isDone && ! $link)
                                    <span class="rounded-full bg-sand px-2 py-0.5 text-[11px] font-normal text-ink-mute">{{ __('app.soon') }}</span>
                                @endif
                            </span>
                            <span class="mt-0.5 block text-sm text-ink-soft">{{ __("app.dashboard.steps.$key.text") }}</span>
                        </span>
                        @if ($link)
                            <x-icon name="arrow" class="flip-rtl mt-1.5 size-4 text-ink-mute transition group-hover:translate-x-0.5 group-hover:text-clay rtl:group-hover:-translate-x-0.5" />
                        @endif
                    </{{ $link ? 'a' : 'div' }}>
                </li>
            @endforeach
        </ol>
    </section>
</x-layouts.dashboard>
