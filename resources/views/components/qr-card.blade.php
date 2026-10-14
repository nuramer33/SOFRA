{{--
    Table card: brand-colored arch with the logo, the shop name, "scan for
    the menu" in each of the shop's languages, the QR and the link.
    Sized in em so the same markup works as a preview and at A6 print size.
--}}
@props(['shop', 'qr'])
<div {{ $attributes->class('relative flex aspect-[105/148] flex-col items-center overflow-hidden bg-[#fffdf9] text-ink') }}
     style="--brand: {{ $shop->brand_color }}; --brand-fg: {{ $shop->brandForeground() }}">
    <div class="absolute inset-x-0 top-0 h-[27%] bg-brand" aria-hidden="true"></div>
    <div class="arch relative z-10 mt-[8%] grid h-[23%] w-[25%] place-items-center overflow-hidden bg-brand text-brand-fg ring-[0.35em] ring-[#fffdf9]">
        @if ($shop->logo_path)
            <img src="{{ $shop->logoUrl() }}" alt="" class="size-full object-contain p-[12%]">
        @else
            <span class="font-display text-[2.6em] leading-none font-semibold">{{ mb_substr($shop->name, 0, 1) }}</span>
        @endif
    </div>

    <p class="relative mt-[5%] px-[8%] text-center font-display text-[1.6em] leading-tight font-semibold">{{ $shop->name }}</p>

    <div class="mt-[2.5%] space-y-[0.15em] text-center text-[0.72em] text-ink-soft">
        @foreach ($shop->contentLocales() as $locale)
            <p lang="{{ $locale }}" dir="{{ \App\Support\Locales::dir($locale) }}">{{ __('app.qr.scan', [], $locale) }}</p>
        @endforeach
    </div>

    {{-- Our own generated SVG (from the menu URL), so it's safe to print raw. --}}
    <div class="mt-auto mb-[4%] w-[52%] [&>svg]:h-auto [&>svg]:w-full">{!! $qr->svg() !!}</div>

    <p class="mb-[6%] text-[0.62em] tracking-wide text-ink-mute" dir="ltr">{{ preg_replace('#^https?://#', '', $shop->menuUrl()) }}</p>
</div>
