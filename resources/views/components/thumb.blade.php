{{-- Small arch-shaped thumbnail, or the first letter when there's no photo. --}}
@props(['src' => null, 'label' => '', 'size' => 'h-16 w-14'])
<span {{ $attributes->class([$size, 'arch relative grid shrink-0 place-items-center overflow-hidden bg-sand ring-1 ring-line/80']) }}>
    @if ($src)
        <img src="{{ $src }}" alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover">
    @else
        <span class="font-display text-xl text-ink-mute">{{ mb_substr($label, 0, 1) }}</span>
    @endif
</span>
