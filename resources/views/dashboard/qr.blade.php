<x-layouts.dashboard :title="__('app.qr.title')">
    <x-page-header :title="__('app.qr.title')" :subtitle="__('app.qr.subtitle')" />

    <div class="grid gap-6 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] lg:items-start">
        {{-- Live preview of the printed card --}}
        <div class="reveal mx-auto w-full max-w-xs lg:max-w-none" style="--i: 1">
            <div class="rotate-[-1.5deg] rounded-[1.25rem] shadow-[0_30px_60px_-30px_rgb(36_26_20/0.45)] ring-1 ring-line transition duration-500 hover:rotate-0">
                <x-qr-card :shop="$shop" :qr="$qr" class="rounded-[1.25rem] text-[15px]" />
            </div>
        </div>

        <div class="space-y-4">
            @foreach ([
                ['route' => route('qr.print'), 'icon' => 'qr', 'title' => __('app.qr.print'), 'hint' => __('app.qr.print_hint'), 'primary' => true, 'target' => '_blank'],
                ['route' => route('qr.download', 'png'), 'icon' => 'image', 'title' => __('app.qr.download_png'), 'hint' => __('app.qr.png_hint'), 'primary' => false, 'target' => null],
                ['route' => route('qr.download', 'svg'), 'icon' => 'sparkle', 'title' => __('app.qr.download_svg'), 'hint' => __('app.qr.svg_hint'), 'primary' => false, 'target' => null],
            ] as $i => $action)
                <a href="{{ $action['route'] }}" @if ($action['target']) target="_blank" rel="noopener" @endif
                   class="reveal card group flex items-center gap-4 p-4 transition duration-300 hover:-translate-y-0.5 hover:ring-clay/30 sm:p-5" style="--i: {{ $i + 2 }}">
                    <span @class(['grid size-12 shrink-0 place-items-center rounded-2xl transition',
                                  'bg-clay text-paper' => $action['primary'],
                                  'bg-sand text-clay group-hover:bg-clay group-hover:text-paper' => ! $action['primary']])>
                        <x-icon :name="$action['icon']" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold">{{ $action['title'] }}</span>
                        <span class="block text-sm text-ink-soft">{{ $action['hint'] }}</span>
                    </span>
                    <x-icon name="arrow" class="flip-rtl size-4 text-ink-mute transition group-hover:text-clay" />
                </a>
            @endforeach

            {{-- One QR for every table: this only builds a link that pre-fills the table number --}}
            <section class="reveal card p-5 sm:p-6" style="--i: 4" x-data="tableLink(@js($qr->url()))">
                <h2 class="font-display text-lg font-semibold">{{ __('qrtable.title') }}</h2>
                <p class="mt-1 text-sm text-ink-soft">{{ __('qrtable.text') }}</p>
                <label class="mt-4 block space-y-1.5">
                    <span class="text-sm font-medium text-ink-soft">{{ __('qrtable.label') }}</span>
                    <input type="text" x-model="table" maxlength="20" autocomplete="off" placeholder="5" dir="ltr" class="field max-w-40 py-3 tabular-nums rtl:text-right">
                </label>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <p class="min-w-0 flex-1 truncate rounded-2xl bg-sand px-4 py-3 text-sm" dir="ltr" x-text="url"></p>
                    <button type="button" @click="copy()" class="btn btn-ghost min-h-11 shrink-0 px-4 text-sm">
                        <x-icon name="copy" class="size-4" />
                        <span x-text="copied ? @js(__('qrtable.copied')) : @js(__('qrtable.copy'))">{{ __('qrtable.copy') }}</span>
                    </button>
                </div>
                <p class="mt-3 text-xs text-ink-mute">{{ __('qrtable.note') }}</p>
            </section>

            <section class="reveal rounded-3xl bg-sand/70 p-5 sm:p-6" style="--i: 5">
                <h2 class="font-display text-lg font-semibold">{{ __('app.qr.tips_title') }}</h2>
                <ul class="mt-3 space-y-2 text-sm text-ink-soft">
                    @foreach (__('app.qr.tips') as $tip)
                        <li class="flex gap-2.5"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-olive" /> {{ $tip }}</li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
</x-layouts.dashboard>
