{{-- Password input with a show/hide toggle (on the "end" side, so it flips in RTL). --}}
@props(['name' => 'password', 'label', 'hint' => null, 'autocomplete' => 'current-password'])
@php
    $error = $errors->first($name);
    $describedBy = trim(($hint ? "$name-hint " : '').($error ? "$name-error" : ''));
@endphp
<div class="space-y-2" x-data="{ show: false }">
    <div class="flex items-center justify-between">
        <label for="{{ $name }}" class="block text-sm font-medium text-ink-soft">{{ $label }}</label>
        {{ $aside ?? '' }}
    </div>
    <div class="relative">
        <input id="{{ $name }}" name="{{ $name }}" type="password" :type="show ? 'text' : 'password'"
               autocomplete="{{ $autocomplete }}" required
               @if ($error) aria-invalid="true" @endif
               @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
               class="field pe-12">
        <button type="button" @click="show = !show"
                class="absolute inset-y-0 end-0 grid w-12 place-items-center text-ink-mute transition-colors hover:text-ink"
                :aria-label="show ? @js(__('app.auth.hide_password')) : @js(__('app.auth.show_password'))"
                aria-label="{{ __('app.auth.show_password') }}">
            <x-icon name="eye" x-show="!show" />
            <x-icon name="eye-off" x-show="show" x-cloak />
        </button>
    </div>
    @if ($hint && ! $error)
        <p id="{{ $name }}-hint" class="text-xs text-ink-mute">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $name }}-error" class="text-sm text-danger" role="alert">{{ $error }}</p>
    @endif
</div>
