{{-- Grip for drag-and-drop ordering (see the `sortable` Alpine component). --}}
<button type="button" data-handle @pointerdown="start($event)"
        {{ $attributes->class('grid h-10 w-7 shrink-0 cursor-grab touch-none place-items-center rounded-lg text-ink-mute transition hover:bg-sand hover:text-ink active:cursor-grabbing') }}
        aria-label="{{ __('app.actions.drag') }}" title="{{ __('app.actions.drag') }}">
    <x-icon name="grip" class="size-4.5" />
</button>