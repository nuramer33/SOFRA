{{-- Label + input + hint + error, wired for screen readers. --}}
@props(['name', 'label', 'type' => 'text', 'hint' => null, 'value' => null, 'dir' => null])
@php
    $id = $attributes->get('id', $name);
    $error = $errors->first($name);
    $describedBy = trim(($hint ? "$id-hint " : '').($error ? "$id-error" : ''));
@endphp
<div class="space-y-2">
    <label for="{{ $id }}" class="block text-sm font-medium text-ink-soft">{{ $label }}</label>

    {{ $before ?? '' }}

    @isset($input)
        {{ $input }}
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
               value="{{ $type === 'password' ? '' : old($name, $value) }}"
               @if ($dir) dir="{{ $dir }}" @endif
               @if ($error) aria-invalid="true" @endif
               @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
               {{ $attributes->except('id')->class(['field', 'rtl:text-right' => $dir === 'ltr']) }}>
    @endisset

    @if ($hint && ! $error)
        <p id="{{ $id }}-hint" class="text-xs text-ink-mute">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="flex items-center gap-1.5 text-sm text-danger" role="alert">{{ $error }}</p>
    @endif
</div>
