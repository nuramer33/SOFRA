{{-- A decorative olive branch: stem, paired leaves and olives. Sways via .leaf. --}}
@props(['olives' => true])
<svg {{ $attributes->merge(['viewBox' => '0 0 200 320', 'fill' => 'none', 'aria-hidden' => 'true']) }}>
    <path d="M100 318C88 240 112 150 98 12" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
    @foreach (range(0, 6) as $i)
        @php
            $y = 286 - $i * 38;
            $x = 100 + sin($i * 0.9) * 8 - $i * 0.4;
            $size = 1 - $i * 0.08;
        @endphp
        <path d="M0 0C12-16 40-16 54 0 40 16 12 16 0 0Z" fill="currentColor" opacity="{{ $i % 2 ? .75 : 1 }}"
              transform="translate({{ $x }} {{ $y }}) rotate({{ -28 - $i * 3 }}) scale({{ $size }})"/>
        <path d="M0 0C12-16 40-16 54 0 40 16 12 16 0 0Z" fill="currentColor" opacity="{{ $i % 2 ? 1 : .75 }}"
              transform="translate({{ $x }} {{ $y - 14 }}) scale(-{{ $size }} {{ $size }}) rotate({{ -28 - $i * 3 }})"/>
    @endforeach
    @if ($olives)
        <circle cx="70" cy="196" r="9" fill="var(--color-clay-deep)"/>
        <circle cx="132" cy="140" r="8" fill="var(--color-clay-deep)"/>
        <circle cx="74" cy="88" r="7" fill="var(--color-clay-deep)"/>
    @endif
</svg>