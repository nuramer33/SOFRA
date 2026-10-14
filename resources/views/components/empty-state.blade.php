{{-- Friendly empty state with an arch illustration and a call to action. --}}
@props(['title', 'text', 'icon' => 'grid'])
<div {{ $attributes->class('reveal card flex flex-col items-center px-6 py-14 text-center') }}>
    <div class="relative mb-6" aria-hidden="true">
        <div class="arch h-28 w-20 bg-sand-deep"></div>
        <div class="arch absolute inset-x-3 bottom-0 grid h-20 place-items-center bg-clay text-paper">
            <x-icon :name="$icon" class="size-6" />
        </div>
    </div>
    <h2 class="font-display text-2xl font-semibold">{{ $title }}</h2>
    <p class="mt-2 max-w-sm text-ink-soft">{{ $text }}</p>
    @isset($action)
        <div class="mt-7">{{ $action }}</div>
    @endisset
</div>
