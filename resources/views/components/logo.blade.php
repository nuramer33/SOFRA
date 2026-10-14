{{-- Arch mark + wordmark. The arch is the brand's signature shape. --}}
@props(['tone' => 'ink', 'mark' => false])
<span {{ $attributes->class(['inline-flex items-center gap-2.5', 'text-ink' => $tone === 'ink', 'text-paper' => $tone === 'paper']) }}>
    <svg viewBox="0 0 32 32" class="size-8 shrink-0" aria-hidden="true">
        <path d="M4 30V14a12 12 0 0 1 24 0v16z" class="{{ $tone === 'paper' ? 'fill-paper' : 'fill-clay' }}"/>
        <path d="M10 30V16a6 6 0 0 1 12 0v14" fill="none" class="{{ $tone === 'paper' ? 'stroke-clay-deep' : 'stroke-paper' }}" stroke-width="2"/>
        <circle cx="16" cy="15.5" r="2.2" class="fill-saffron"/>
    </svg>
    @unless ($mark)
        <span class="font-display text-2xl font-semibold leading-none">{{ __('app.brand') }}</span>
    @endunless
</span>
