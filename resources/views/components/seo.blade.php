{{--
    Everything a search engine or a chat app needs to understand a page:
    description, canonical link, Open Graph + Twitter preview, language
    alternates and optional structured data (JSON-LD). Put it in the head slot.
--}}
@props([
    'title', 'description' => null, 'image' => null, 'url' => null, 'type' => 'website',
    'jsonLd' => null, 'noindex' => false, 'alternates' => [],
])
@php
    $url ??= url()->current();
    $image ??= asset('og-default.png');
    $ogLocale = ['ar' => 'ar_AR', 'tr' => 'tr_TR', 'en' => 'en_US'][app()->getLocale()] ?? 'en_US';
@endphp
@if ($description)
    <meta name="description" content="{{ $description }}">
@endif
@if ($noindex)
    <meta name="robots" content="noindex, nofollow">
@else
    <link rel="canonical" href="{{ $url }}">
@endif
@foreach ($alternates as $hreflang => $href)
    <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}">
@endforeach
<meta property="og:site_name" content="{{ __('app.brand') }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $title }}">
@if ($description)
    <meta property="og:description" content="{{ $description }}">
@endif
<meta property="og:url" content="{{ $url }}">
<meta property="og:locale" content="{{ $ogLocale }}">
<meta property="og:image" content="{{ $image }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
@if ($description)
    <meta name="twitter:description" content="{{ $description }}">
@endif
<meta name="twitter:image" content="{{ $image }}">
@if ($jsonLd)
    {{-- JSON_HEX_* keeps user text from ever closing the script tag --}}
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endif