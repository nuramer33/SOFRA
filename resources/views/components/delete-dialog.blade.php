{{--
    Confirmation using the native <dialog> element (accessible,
    focus-trapped, Esc closes). Defaults to a destructive DELETE.

    - Default: renders its own trigger button (style it via attributes / `trigger` slot).
    - With `event="delete-product-5"`: no button; opens when that window event
      fires (used from dropdown menus, which can't contain an open dialog).
    - `method` / `confirm` / `danger` turn it into any confirmed action
      (e.g. PATCH "Suspend shop").
--}}
@props(['action', 'title', 'text', 'event' => null, 'method' => 'DELETE', 'confirm' => null, 'danger' => true])
<div x-data class="contents" @if ($event) x-on:{{ $event }}.window="$refs.dialog.showModal()" @endif>
    @unless ($event)
        <button type="button" @click="$refs.dialog.showModal()" {{ $attributes->merge(['class' => $danger ? 'btn text-danger hover:bg-danger/10' : 'btn btn-ghost']) }}>
            {{ $trigger ?? __('app.actions.delete') }}
        </button>
    @endunless

    <dialog x-ref="dialog" @click.self="$refs.dialog.close()"
            class="m-auto w-[min(26rem,calc(100vw-2rem))] rounded-3xl bg-paper p-0 text-ink shadow-2xl backdrop:bg-ink/40 backdrop:backdrop-blur-sm open:animate-[pop_.35s_var(--ease-spring)]">
        <form method="POST" action="{{ $action }}" class="p-6 sm:p-7">
            @csrf
            @method($method)
            <span @class(['grid size-12 place-items-center rounded-full', 'bg-danger/10 text-danger' => $danger, 'bg-olive-soft text-olive' => ! $danger])>
                <x-icon :name="$danger ? 'trash' : 'check'" />
            </span>
            <h2 class="mt-4 font-display text-2xl font-semibold">{{ $title }}</h2>
            <p class="mt-2 text-ink-soft">{{ $text }}</p>
            <div class="mt-7 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="btn btn-ghost" @click="$refs.dialog.close()" autofocus>{{ __('app.actions.cancel') }}</button>
                <button type="submit" @class(['btn text-white', 'bg-danger hover:bg-danger/90' => $danger, 'bg-olive hover:bg-olive/90' => ! $danger])>
                    {{ $confirm ?? __('app.actions.delete') }}
                </button>
            </div>
        </form>
    </dialog>
</div>
