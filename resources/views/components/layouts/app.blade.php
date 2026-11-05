<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Weather Dashboard') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>
{{--
    data-theme: dikirim dari view, dibaca JS untuk apply class ke body
    Ini cara bersih agar server-side logic tidak tercecer ke JS
--}}
<body class="min-h-screen text-white antialiased transition-all duration-700"
      data-theme="{{ $theme ?? '' }}">

    <x-navbar />

    <main>{{ $slot }}</main>

    <footer class="border-t border-white/10 mt-8"
            style="background: rgba(0,0,0,0.2);">
        <div class="max-w-6xl mx-auto px-4 py-4 text-center">
            <p class="text-xs text-white/40">
                &copy; {{ date('Y') }} {{ config('app.name') }} &mdash;
                Powered by <a href="https://www.weatherapi.com" target="_blank"
                    class="text-white/60 hover:text-white transition-colors">WeatherAPI.com</a>
            </p>
        </div>
    </footer>

</body>
</html>
