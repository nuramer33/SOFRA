<x-layouts.base :title="__('app.errors.404_title')" body-class="grain">
    <main class="mx-auto flex min-h-dvh max-w-md flex-col items-center justify-center px-6 text-center">
        <div class="reveal relative" aria-hidden="true">
            <div class="arch h-40 w-28 bg-sand-deep"></div>
            <div class="arch absolute inset-x-4 bottom-0 grid h-28 place-items-center bg-clay font-display text-3xl text-paper">404</div>
        </div>
        <h1 class="reveal mt-8 font-display text-3xl font-semibold" style="--i: 1">{{ __('app.errors.404_title') }}</h1>
        <p class="reveal mt-2 text-ink-soft" style="--i: 2">{{ __('app.errors.404_text') }}</p>
        <a href="{{ route('home') }}" class="reveal btn btn-primary mt-8" style="--i: 3">{{ __('app.errors.home') }}</a>
    </main>
</x-layouts.base>
