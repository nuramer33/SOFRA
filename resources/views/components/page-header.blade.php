{{-- Page title row. Optional `back` link and `actions` slot (shown at the end side). --}}
@props(['title', 'subtitle' => null, 'back' => null])
<header {{ $attributes->class('reveal mb-8 flex flex-wrap items-end justify-between gap-4') }}>
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-3 inline-flex items-center gap-1.5 text-sm text-ink-soft transition hover:text-clay">
                <x-icon name="arrow" class="flip-rtl size-4 rotate-180" /> {{ __('app.actions.back') }}
            </a>
        @endif
        <h1 class="font-display text-3xl font-semibold sm:text-4xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1.5 text-ink-soft">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
</header>
