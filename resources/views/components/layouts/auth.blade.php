<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{--
    Layout khusus halaman auth (login/register).
    Menggunakan dynamic background WIB seperti dashboard utama —
    siang = theme-sunny, malam = theme-night.
--}}
<body class="min-h-screen text-white antialiased flex flex-col"
      data-theme="{{ \Carbon\Carbon::now('Asia/Jakarta')->hour >= 6 && \Carbon\Carbon::now('Asia/Jakarta')->hour < 18 ? 'theme-sunny' : 'theme-night' }}">

    {{-- Navbar minimal --}}
    <nav class="sticky top-0 z-50 border-b border-white/10"
         style="background: rgba(0,0,0,0.25); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                    <div class="w-8 h-8 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center
                                group-hover:bg-white/20 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="4"/>
                            <path stroke-linecap="round" d="M12 2v2m0 16v2M4.22 4.22l1.42 1.42m12.72 12.72 1.42 1.42M2 12h2m16 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-white text-sm tracking-tight">
                        {{ config('app.name', 'Weather Dashboard') }}
                    </span>
                </a>

                <div class="hidden sm:block text-right">
                    <p class="text-xs font-medium text-white/90">
                        {{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }}
                    </p>
                    <p class="text-xs text-white/50">
                        <span id="navbar-clock">
                            {{ \Carbon\Carbon::now('Asia/Jakarta')->format('H:i') }} WIB
                        </span>
                        — Bandung, Indonesia
                    </p>
                </div>
            </div>
        </div>
    </nav>

    {{-- Konten form auth --}}
    <main class="flex-1 flex items-center justify-center px-4 py-12">
        {{ $slot }}
    </main>

    <footer class="border-t border-white/10"
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
