{{-- Tiny "ع TR EN" markers: filled = translated, faded = missing. Helps owners spot gaps. --}}
@props(['model', 'field'])
@php
    $shop = app(\App\Tenancy\CurrentShop::class)->get();
    $translated = $model->getTranslatedLocales($field);
    $short = ['ar' => 'ع', 'tr' => 'TR', 'en' => 'EN'];
@endphp
@if (count($shop->contentLocales()) > 1)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1']) }} aria-hidden="true">
        @foreach ($shop->contentLocales() as $locale)
            <span @class(['rounded px-1 text-[10px] leading-4 font-medium',
                          'bg-olive-soft text-olive' => in_array($locale, $translated),
                          'bg-sand/70 text-ink-mute/60 line-through' => ! in_array($locale, $translated)])>{{ $short[$locale] ?? $locale }}</span>
        @endforeach
    </span>
@endif
