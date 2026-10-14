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
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cpath d='M4 30V14a12 12 0 0 1 24 0v16z' fill='%2355642f'/%3E%3Ccircle cx='16' cy='16' r='4' fill='%23f4f2e7'/%3E%3C/svg%3E">
    {{ $head ?? '' }}
    @vite(['resources/css/app.css', ...$scripts])
</head>
<body {{ $attributes->class(['min-h-dvh', $bodyClass]) }}>
    {{ $slot }}
</body>
</html>
