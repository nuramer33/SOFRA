{{--
    Photo picker: tap or drop, instant preview, shrinks big phone photos
    before upload. `shape` is 'arch' (products), 'circle' (logo) or 'wide'.
--}}
@props(['name' => 'image', 'removeName' => 'remove_image', 'label', 'url' => null, 'hint' => null, 'shape' => 'arch', 'maxSide' => 2000])
@php
    $error = $errors->first($name);
    $frame = match ($shape) {
        'circle' => 'size-36 rounded-full',
        'wide' => 'aspect-[16/9] w-full rounded-3xl',
        default => 'arch aspect-[4/5] w-full max-w-64',
    };
@endphp
<div x-data="imageInput({ url: @js($url), maxSide: {{ $maxSide }} })" class="space-y-3">
    <span class="block text-sm font-medium text-ink-soft">{{ $label }}</span>

    <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-end">
        <label @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="pick($event)"
               :class="dragging && 'ring-4 ring-clay/30 scale-[1.02]'"
               class="{{ $frame }} group relative grid shrink-0 cursor-pointer place-items-center overflow-hidden bg-sand ring-1 ring-line transition duration-300 ease-(--ease-soft) hover:ring-clay/40">
            <input type="file" x-ref="file" name="{{ $name }}" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="pick($event)"
                   @if ($error) aria-invalid="true" @endif>

            {{-- Empty state --}}
            <span x-show="!preview" class="flex flex-col items-center gap-2 p-4 text-center text-sm text-ink-soft transition group-hover:text-clay">
                <span class="grid size-12 place-items-center rounded-full bg-paper shadow-sm"><x-icon name="image" class="size-5" /></span>
                {{ __('app.image.choose') }}
            </span>

            {{-- Preview (keyed so a new image fades in) --}}
            <template x-if="preview">
                <img :src="preview" alt="" class="absolute inset-0 size-full animate-[pop_.5s_var(--ease-soft)] object-cover"
                     :class="busy && 'opacity-60 blur-[2px]'">
            </template>

            <span x-show="busy" x-cloak class="absolute inset-x-3 bottom-3 rounded-full bg-ink/80 px-3 py-1.5 text-center text-xs text-paper backdrop-blur">
                {{ __('app.image.optimizing') }}
            </span>
        </label>

        <div class="space-y-2 text-center sm:text-start">
            <div class="flex flex-wrap justify-center gap-2 sm:justify-start" x-show="preview">
                <button type="button" class="btn btn-ghost px-4 py-2 text-sm" @click="$refs.file.click()">{{ __('app.image.change') }}</button>
                <button type="button" class="btn px-4 py-2 text-sm text-danger hover:bg-danger/10" @click="remove()">{{ __('app.image.remove') }}</button>
            </div>
            <p x-show="removed" x-cloak class="text-sm text-ink-soft">
                {{ __('app.image.removed') }}
                <button type="button" class="font-medium text-clay underline-offset-4 hover:underline" @click="undo()">{{ __('app.image.undo') }}</button>
            </p>
            <p class="max-w-xs text-xs text-ink-mute">{{ $hint ?? __('app.image.hint') }}</p>
            @if ($error)
                <p class="text-sm text-danger" role="alert">{{ $error }}</p>
            @endif
        </div>
    </div>

    <input type="hidden" name="{{ $removeName }}" :value="removed ? 1 : 0" value="0">
</div>
