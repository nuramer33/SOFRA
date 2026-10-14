{{--
    One language switcher for the whole form. Fields made with
    <x-translatable-field> show only the active language.
    A red dot marks a language that has a validation error.
    Needs an ancestor with x-data="{ lang: '...' }".
--}}
@props(['locales', 'fields' => [], 'required' => null])
@php
    $hasError = fn (string $locale) => collect($fields)->contains(fn ($field) => $errors->has("{$field}.{$locale}"));
@endphp
<div {{ $attributes->class('flex items-center justify-between gap-3') }}>
    <span class="text-sm text-ink-soft">{{ __('app.forms.content_language') }}</span>
    <div class="inline-flex rounded-full bg-sand p-1 ring-1 ring-line" role="tablist">
        @foreach ($locales as $locale)
            <button type="button" role="tab" @click="lang = @js($locale)" :aria-selected="lang === @js($locale)"
                    class="relative rounded-full px-3.5 py-1.5 text-sm font-medium transition-colors duration-200"
                    :class="lang === @js($locale) ? 'bg-ink text-paper shadow-sm' : 'text-ink-soft hover:text-ink'"
                    lang="{{ $locale }}">
                {{ \App\Support\Locales::name($locale) }}
                @if ($hasError($locale))
                    <span class="absolute -top-0.5 -end-0.5 size-2.5 rounded-full bg-danger ring-2 ring-sand" title="{{ __('app.forms.has_errors') }}"></span>
                @endif
            </button>
        @endforeach
    </div>
</div>
