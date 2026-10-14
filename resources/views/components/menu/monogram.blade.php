{{-- The shop's logo, or its first letter set in the display face. --}}
@props(['shop', 'size' => 'text-5xl', 'padding' => 'p-5'])
@if ($shop->logo_path)
    <img src="{{ $shop->logoUrl() }}" alt="{{ $shop->name }}" {{ $attributes->class(['size-full object-contain', $padding]) }}>
@else
    <span {{ $attributes->class(['font-display font-semibold leading-none', $size]) }} aria-hidden="true">{{ mb_substr($shop->name, 0, 1) }}</span>
@endif
