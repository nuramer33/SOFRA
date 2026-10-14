{{--
    Language picker: a button showing the current language that opens a
    small menu. Plain links inside, so switching works without JavaScript
    once the menu is open (and the button is a normal focusable control).

    Props:
      tone      light | dark      — button colors for light or dark backgrounds
      direction down | up         — open below (default) or above (e.g. sidebar footer)
      compact   bool              — button shows only the short code (tight headers)
      locales   list|null         — which languages to offer (default: all)
      urls      [code => url]|null — where each language links (default: locale.switch)
--}}
@props(['tone' => 'light', 'direction' => 'down', 'compact' => false, 'locales' => null, 'urls' => null])
@php
    $codes = $locales ?? \App\Support\Locales::codes();
    $current = app()->getLocale();
    $short = ['ar' => 'ع', 'tr' => 'TR', 'en' => 'EN'];
    $urlFor = fn (string $code) => $urls[$code] ?? route('locale.switch', $code);
@endphp
<div x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false"
     {{ $attributes->class('relative') }}>
    <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="menu"
            aria-label="{{ __('app.language') }}: {{ \App\Support\Locales::name($current) }}"
            @class([
                'inline-flex h-10 items-center gap-2 rounded-full ps-3 pe-3 text-sm font-medium ring-1 transition duration-200 active:scale-95',
                'bg-white/70 text-ink ring-line hover:bg-white' => $tone === 'light',
                'bg-paper/10 text-paper ring-paper/20 hover:bg-paper/20' => $tone === 'dark',
            ])>
        <x-icon name="globe" class="size-[1.1rem] opacity-70" />
        <span lang="{{ $current }}">{{ $compact ? ($short[$current] ?? $current) : \App\Support\Locales::name($current) }}</span>
        <span class="transition-transform duration-300" :class="open && 'rotate-180'"><x-icon name="chevron-down" class="size-3.5 opacity-60" /></span>
    </button>

    <div x-show="open" x-cloak role="menu"
         x-transition:enter="transition duration-200 ease-(--ease-spring)"
         x-transition:enter-start="scale-95 opacity-0"
         x-transition:leave="transition duration-150 ease-in"
         x-transition:leave-end="scale-95 opacity-0"
         @class([
             'absolute end-0 z-50 w-56 rounded-2xl bg-paper p-1.5 text-ink shadow-[0_20px_50px_-20px_rgb(36_26_20/0.45)] ring-1 ring-line',
             'top-full mt-2 ltr:origin-top-right rtl:origin-top-left' => $direction === 'down',
             'bottom-full mb-2 ltr:origin-bottom-right rtl:origin-bottom-left' => $direction === 'up',
         ])>
        <p class="px-3 pt-2 pb-1.5 text-xs text-ink-mute">{{ __('app.language') }}</p>
        @foreach ($codes as $code)
            @php($active = $code === $current)
            <a href="{{ $urlFor($code) }}" role="menuitemradio" aria-checked="{{ $active ? 'true' : 'false' }}"
               hreflang="{{ $code }}" lang="{{ $code }}"
               @class(['flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 transition-colors duration-150',
                       'bg-sand font-semibold' => $active, 'hover:bg-sand/70' => ! $active])>
                <span class="flex items-center gap-3">
                    <span @class(['grid size-8 place-items-center rounded-full text-xs font-semibold ring-1',
                                  'bg-ink text-paper ring-ink' => $active, 'bg-white text-ink-soft ring-line' => ! $active])>{{ $short[$code] ?? strtoupper($code) }}</span>
                    {{ \App\Support\Locales::name($code) }}
                </span>
                @if ($active)
                    <x-icon name="check" class="size-4 text-clay" />
                @endif
            </a>
        @endforeach
    </div>
</div>
