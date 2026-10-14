{{--
    A field written in several languages: name[ar], name[tr], name[en].
    Each language keeps its own text direction, whatever the interface language.
--}}
@props([
    'name', 'label', 'locales', 'values' => [], 'required' => null,
    'multiline' => false, 'maxlength' => null, 'placeholder' => null,
])
<div class="space-y-2">
    @foreach ($locales as $locale)
        @php
            $key = "{$name}.{$locale}";
            $id = "{$name}_{$locale}";
            $error = $errors->first($key);
            $value = old("{$name}.{$locale}", $values[$locale] ?? '');
        @endphp
        <div x-show="lang === @js($locale)" @if (! $loop->first) x-cloak @endif class="space-y-2">
            <label for="{{ $id }}" class="flex items-center gap-2 text-sm font-medium text-ink-soft">
                {{ $label }}
                <span class="rounded-full bg-sand px-2 py-0.5 text-[11px] font-normal" lang="{{ $locale }}">{{ \App\Support\Locales::name($locale) }}</span>
                @if ($locale === $required)
                    <span class="text-[11px] font-normal text-clay">{{ __('app.forms.required') }}</span>
                @endif
            </label>

            @if ($multiline)
                <textarea id="{{ $id }}" name="{{ $name }}[{{ $locale }}]" rows="3"
                          lang="{{ $locale }}" dir="{{ \App\Support\Locales::dir($locale) }}"
                          @if ($maxlength) maxlength="{{ $maxlength }}" @endif
                          @if ($placeholder && $locale === $required) placeholder="{{ $placeholder }}" @endif
                          @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
                          class="field resize-y leading-relaxed">{{ $value }}</textarea>
            @else
                <input id="{{ $id }}" name="{{ $name }}[{{ $locale }}]" value="{{ $value }}"
                       lang="{{ $locale }}" dir="{{ \App\Support\Locales::dir($locale) }}"
                       @if ($maxlength) maxlength="{{ $maxlength }}" @endif
                       @if ($placeholder && $locale === $required) placeholder="{{ $placeholder }}" @endif
                       @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
                       class="field">
            @endif

            @if ($error)
                <p id="{{ $id }}-error" class="text-sm text-danger" role="alert">{{ $error }}</p>
            @endif
        </div>
    @endforeach
</div>
