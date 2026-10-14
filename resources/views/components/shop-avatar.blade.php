{{-- Shop logo, or its first letter on the shop's brand color. --}}
@props(['shop', 'size' => 'size-10'])
@if ($shop->logo_path)
    <img src="{{ Storage::url($shop->logo_path) }}" alt="" {{ $attributes->class([$size, 'shrink-0 rounded-full object-cover ring-1 ring-line']) }}>
@else
    <span {{ $attributes->class([$size, 'grid shrink-0 place-items-center rounded-full font-display text-lg font-semibold text-paper']) }}
          style="background-color: {{ $shop->brand_color }}">{{ mb_substr($shop->name, 0, 1) }}</span>
@endif
