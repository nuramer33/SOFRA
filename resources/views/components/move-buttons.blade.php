{{-- Up / down arrows for manual ordering. --}}
@props(['action', 'first' => false, 'last' => false])
<div {{ $attributes->class('flex flex-col overflow-hidden rounded-xl ring-1 ring-line') }}>
    @foreach (['up' => ! $first, 'down' => ! $last] as $direction => $enabled)
        <form method="POST" action="{{ $action }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="direction" value="{{ $direction }}">
            <button type="submit" @disabled(! $enabled)
                    class="grid h-7 w-9 place-items-center bg-white/60 text-ink-soft transition hover:bg-white hover:text-clay active:scale-90 disabled:opacity-30 disabled:hover:bg-white/60"
                    aria-label="{{ __('app.actions.move_'.$direction) }}" title="{{ __('app.actions.move_'.$direction) }}">
                <x-icon name="chevron-{{ $direction }}" class="size-4" />
            </button>
        </form>
    @endforeach
</div>
