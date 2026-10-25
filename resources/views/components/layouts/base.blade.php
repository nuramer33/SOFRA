@props([
    'title' => null,
    'exactTitle' => null,
    'bodyClass' => '',
    'htmlClass' => null,
    'themeColor' => '#f4f2e7',
    'scripts' => ['resources/js/app.js'],
])
@php($locale = app()->getLocale())
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ \App\Support\Locales::dir($locale) }}"@if ($htmlClass) class="{{ $htmlClass }}"@endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ $themeColor }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $exactTitle ?? ($title ? $title.' · '.__('app.brand') : __('app.brand').' — '.__('app.tagline')) }}</title>
    <link rel="icon" type="image/png" href="{{ asset('icons/favicon.png') }}">
    {{ $head ?? '' }}
    @vite(['resources/css/app.css', ...$scripts])
</head>
<body {{ $attributes->class(['min-h-dvh', $bodyClass]) }}>
    {{ $slot }}
</body>
</html>
