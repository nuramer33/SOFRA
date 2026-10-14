{{-- On/off switch for a boolean form field. The hidden input sends 0 when off. --}}
@props(['name', 'label', 'hint' => null, 'checked' => false])
@php($id = $attributes->get('id', $name))
<label for="{{ $id }}" class="flex cursor-pointer items-start justify-between gap-4 rounded-2xl p-1">
    <span>
        <span class="block font-medium">{{ $label }}</span>
        @if ($hint)
            <span class="mt-0.5 block text-sm text-ink-soft">{{ $hint }}</span>
        @endif
    </span>
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" role="switch" @checked(old($name, $checked)) class="peer sr-only">
    <span aria-hidden="true"
          class="relative mt-0.5 h-7 w-12 shrink-0 rounded-full bg-sand-deep transition-colors duration-300 peer-checked:bg-olive peer-focus-visible:ring-4 peer-focus-visible:ring-clay/20
                 after:absolute after:top-1 after:start-1 after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform after:duration-300 after:ease-(--ease-spring)
                 peer-checked:after:translate-x-5 rtl:peer-checked:after:-translate-x-5"></span>
</label>
