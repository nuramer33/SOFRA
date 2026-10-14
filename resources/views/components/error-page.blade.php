{{-- Friendly full-page error, same look as the 404. `code` picks the texts in lang/*/errors.php. --}}
@props(['code'])
@php($retry = __("errors.{$code}.retry"))
<x-layouts.base :title="__('errors.'.$code.'.title')" body-class="grain">
    <main class="mx-auto flex min-h-dvh max-w-md flex-col items-center justify-center px-6 text-center">
        <div class="reveal relative" aria-hidden="true">
            <div class="arch h-40 w-28 bg-sand-deep"></div>
            <div class="arch absolute inset-x-4 bottom-0 grid h-28 place-items-center bg-clay font-display text-3xl text-paper">{{ $code }}</div>
        </div>
        <h1 class="reveal mt-8 font-display text-3xl font-semibold" style="--i: 1">{{ __("errors.{$code}.title") }}</h1>
        <p class="reveal mt-2 text-ink-soft" style="--i: 2">{{ __("errors.{$code}.text") }}</p>
        <div class="reveal mt-8 flex flex-wrap justify-center gap-3" style="--i: 3">
            @if ($retry !== "errors.{$code}.retry")
                <button type="button" onclick="location.reload()" class="btn btn-primary">{{ $retry }}</button>
            @endif
            <a href="{{ url('/') }}" class="btn {{ $retry !== "errors.{$code}.retry" ? 'btn-ghost' : 'btn-primary' }}">{{ __('app.errors.home') }}</a>
        </div>
    </main>
</x-layouts.base>