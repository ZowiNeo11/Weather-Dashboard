{{--
    Dashboard — Sprint 3
    Variabel dari DashboardController:
      $weather  : ?WeatherData
      $error    : ?string
      $lastCity : ?string
--}}
<x-layouts.app
    title="{{ $weather ? $weather->city . ' — Cuaca' : 'Weather Dashboard' }}"
    theme="{{ $weather ? $weather->bgThemeClass() : $fallbackTheme }}"
>

{{-- ============================================================
     SEARCH HERO
     ============================================================ --}}
<section class="pt-10 pb-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto text-center">

        {{-- Flash status dari login/register/logout --}}
        @if(session('status'))
            <div class="mb-6 p-3.5 rounded-xl bg-green-500/15 border border-green-500/25
                        text-green-300 text-sm text-center weather-enter">
                {{ session('status') }}
            </div>
        @endif

        @if(! $weather && ! $error)
            <h1 class="text-4xl sm:text-5xl font-bold text-white mb-3 tracking-tight">
                Cuaca Hari Ini
            </h1>
            <p class="text-white/60 mb-8 text-base">
                Masukkan nama kota untuk melihat kondisi cuaca real-time
            </p>
        @endif

        {{-- Form pencarian --}}
        <form action="{{ route('weather.search') }}" method="POST"
              id="search-form" class="flex gap-2">
            @csrf
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white/50"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path stroke-linecap="round" d="m21 21-4.35-4.35"/>
                    </svg>
                </div>
                <input type="text" name="city" id="city-input"
                    value="{{ old('city', $lastCity) }}"
                    placeholder="Jakarta, London, Tokyo..."
                    autocomplete="off"
                    class="glass-input w-full pl-11 pr-4 py-3.5 rounded-xl text-sm font-medium
                           @error('city') ring-2 ring-rose-400/50 @enderror">
            </div>

            <button type="submit" id="search-btn"
                class="px-5 py-3.5 bg-white/20 hover:bg-white/30 border border-white/20
                       text-white font-semibold rounded-xl transition-all duration-200
                       flex items-center gap-2 flex-shrink-0 disabled:opacity-50">
                <svg id="loading-spinner" class="w-4 h-4 animate-spin hidden"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                        stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span id="search-label">Cari</span>
            </button>
        </form>

        @error('city')
            <p class="mt-2.5 text-sm text-rose-300 flex items-center justify-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path stroke-linecap="round" d="M12 8v4m0 4h.01"/>
                </svg>
                {{ $message }}
            </p>
        @enderror

        {{-- Quick suggestions --}}
        @if(! $weather && ! $error)
            <div class="mt-5 flex flex-wrap justify-center gap-2">
                @foreach(['Jakarta', 'Bandung', 'Surabaya', 'Bali', 'Yogyakarta', 'London', 'Tokyo'] as $s)
                    <button type="button" data-suggestion="{{ $s }}"
                        class="px-3.5 py-1.5 rounded-full text-xs font-medium
                               bg-white/10 border border-white/15 text-white/70
                               hover:bg-white/20 hover:text-white transition-all">
                        {{ $s }}
                    </button>
                @endforeach
            </div>
        @endif

    </div>
</section>

{{-- ============================================================
     SKELETON LOADING OVERLAY
     ============================================================ --}}
<div id="skeleton-overlay"
     class="hidden fixed inset-0 z-40 items-center justify-center"
     style="background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);">
    <div class="text-center">
        <div class="w-16 h-16 rounded-2xl bg-white/10 border border-white/20
                    flex items-center justify-center mx-auto mb-4 animate-pulse">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white/50"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="4"/>
                <path stroke-linecap="round" d="M12 2v2m0 16v2M4.22 4.22l1.42 1.42m12.72 12.72 1.42 1.42M2 12h2m16 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
            </svg>
        </div>
        <p class="text-white/70 text-sm font-medium">Mengambil data cuaca...</p>
        <div class="flex justify-center gap-1 mt-3">
            <span class="w-1.5 h-1.5 rounded-full bg-white/40 animate-bounce" style="animation-delay:0s"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-white/40 animate-bounce" style="animation-delay:0.15s"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-white/40 animate-bounce" style="animation-delay:0.3s"></span>
        </div>
    </div>
</div>

{{-- ============================================================
     KONTEN UTAMA
     ============================================================ --}}
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12 space-y-5">

    {{-- ===== ERROR STATE ===== --}}
    @if($error)
        <div class="glass-card p-5 flex items-start gap-4 weather-enter border-rose-500/30
                    bg-rose-500/10">
            <div class="w-10 h-10 rounded-xl bg-rose-500/20 flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-rose-300"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-rose-300 mb-0.5">Pencarian Gagal</p>
                <p class="text-sm text-rose-300/80">{{ $error }}</p>
                <p class="text-xs text-white/40 mt-1.5">
                    Coba periksa ejaan nama kota atau gunakan nama dalam Bahasa Inggris.
                </p>
            </div>
        </div>
    @endif

    {{-- ===== EMPTY STATE ===== --}}
    @if(! $weather && ! $error)
        <div class="text-center py-20 weather-enter">
            <div class="w-24 h-24 rounded-3xl bg-white/10 border border-white/15
                        flex items-center justify-center mx-auto mb-6
                        shadow-[0_0_60px_rgba(255,255,255,0.05)]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-white/40"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="4"/>
                    <path stroke-linecap="round" d="M12 2v2m0 16v2M4.22 4.22l1.42 1.42m12.72 12.72 1.42 1.42M2 12h2m16 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-white/80 mb-2">Selamat Datang!</h2>
            <p class="text-white/40 text-sm max-w-xs mx-auto">
                Ketik nama kota di kolom pencarian di atas dan tekan Cari.
            </p>
        </div>
    @endif

    {{-- ===== HASIL CUACA ===== --}}
    @if($weather instanceof \App\DTOs\WeatherData)

    {{-- ── Baris 1: Current + Detail ── --}}
    {{--
        Grid responsif:
          < 1024px (mobile/tablet) : 1 kolom — stacked
          1024–1279px (laptop)     : 2 kolom — Current | Detail berdampingan
          ≥ 1280px (desktop+)      : 3 kolom — Current(2/3) | Detail(1/3)
    --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5">

        {{-- CURRENT WEATHER (full di lg, 2/3 di xl+) --}}
        <div class="lg:col-span-1 xl:col-span-2 glass-card p-6 weather-enter stagger-1">

            {{-- Lokasi --}}
            <div class="flex items-start justify-between mb-5">
                <div>
                    <div class="flex items-center gap-1.5 text-white/50 text-xs mb-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ $weather->region }}, {{ $weather->country }}
                    </div>
                    <h2 class="text-2xl font-bold text-white">{{ $weather->city }}</h2>
                    <p class="text-xs text-white/40 mt-0.5">{{ $weather->formattedLocalTime() }}</p>
                </div>
                {{-- Badge isDay --}}
                <span class="px-3 py-1 rounded-full text-xs font-medium
                             {{ $weather->isDay ? 'bg-amber-400/20 text-amber-300' : 'bg-indigo-400/20 text-indigo-300' }}">
                    {{ $weather->isDay ? '☀️ Siang' : '🌙 Malam' }}
                </span>
            </div>

            {{-- Favorite Button: Add / Favorited / Login hint --}}
            <div class="mb-5">
                <x-favorite-button
                    :city="$weather->city"
                    :country="$weather->country"
                    :isFavorited="$isFavorited"
                    :favoriteRecord="$favoriteRecord"
                />
            </div>{{-- ← tutup div mb-5 sebelum blok suhu --}}

            {{-- Suhu besar + kondisi --}}
            <div class="flex items-center gap-5 mb-6">
                <img src="{{ $weather->conditionIcon }}"
                     alt="{{ $weather->conditionText }}"
                     class="w-24 h-24 object-contain drop-shadow-lg"
                     loading="eager">
                <div>
                    <div class="flex items-start gap-1">
                        <span class="text-7xl font-thin text-white leading-none">
                            {{ number_format($weather->tempC, 0) }}
                        </span>
                        <span class="text-3xl text-white/60 mt-2">°C</span>
                    </div>
                    <p class="text-lg font-medium text-white/80 mt-1">
                        {{ $weather->conditionText }}
                    </p>
                    <p class="text-sm text-white/40 mt-0.5">
                        Terasa seperti
                        <span class="text-white/70 font-medium">
                            {{ number_format($weather->feelsLikeC, 1) }}°C
                        </span>
                    </p>
                </div>
            </div>

            {{-- Metrik cepat: 4 kolom --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

                <div class="glass-card-sm p-3 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-300 mx-auto mb-1.5"
                        fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                    </svg>
                    <p class="text-lg font-bold text-white">{{ $weather->humidity }}%</p>
                    <p class="text-xs text-white/40">Kelembaban</p>
                </div>

                <div class="glass-card-sm p-3 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-cyan-300 mx-auto mb-1.5"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M9.59 4.59A2 2 0 1 1 11 8H2m10.59 11.41A2 2 0 1 0 14 16H2m15.73-8.27A2.5 2.5 0 1 1 19.5 12H2"/>
                    </svg>
                    <p class="text-lg font-bold text-white">{{ number_format($weather->windKph, 0) }}</p>
                    <p class="text-xs text-white/40">km/h {{ $weather->windDir }}</p>
                </div>

                <div class="glass-card-sm p-3 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-purple-300 mx-auto mb-1.5"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M12 22C6.48 22 2 17.52 2 12S6.48 2 12 2s10 4.48 10 10"/>
                        <path stroke-linecap="round" d="M12 6v6l4 2"/>
                    </svg>
                    <p class="text-lg font-bold text-white">{{ number_format($weather->pressureMb, 0) }}</p>
                    <p class="text-xs text-white/40">hPa</p>
                </div>

                <div class="glass-card-sm p-3 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-300 mx-auto mb-1.5"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <p class="text-lg font-bold text-white">{{ number_format($weather->visibilityKm, 1) }}</p>
                    <p class="text-xs text-white/40">km Visibilitas</p>
                </div>

            </div>
        </div>

        {{-- DETAIL PANEL (1/3 lebar di xl+) --}}
        <div class="glass-card p-5 weather-enter stagger-2 flex flex-col gap-1">
            <h3 class="text-xs font-semibold text-white/50 uppercase tracking-widest mb-2">
                Detail Atmosfer
            </h3>

            <x-detail-item icon="thermometer" label="Suhu"
                :value="number_format($weather->tempC, 1)" unit="°C" />
            <x-detail-item icon="droplet" label="Kelembaban"
                :value="$weather->humidity" unit="%" />
            <x-detail-item icon="wind" label="Angin"
                :value="number_format($weather->windKph, 1)" unit="km/h"
                :sub="$weather->windDir . ' · ' . $weather->windDegree . '°'" />
            <x-detail-item icon="gauge" label="Tekanan"
                :value="number_format($weather->pressureMb, 0)" unit="hPa" />
            <x-detail-item icon="eye" label="Visibilitas"
                :value="number_format($weather->visibilityKm, 1)" unit="km" />
            <x-detail-item icon="droplet" label="Curah Hujan"
                :value="number_format($weather->precipMm, 1)" unit="mm" />

            {{-- UV Index --}}
            <div class="flex items-center gap-3 py-2.5">
                <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-300"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="4"/>
                        <path stroke-linecap="round" d="M12 2v2m0 16v2M4.22 4.22l1.42 1.42m12.72 12.72 1.42 1.42M2 12h2m16 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                    </svg>
                </div>
                <span class="text-sm text-white/60 flex-1">UV Index</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $weather->uvColorClass() }}">
                    {{ number_format($weather->uvIndex, 0) }} — {{ $weather->uvLabel() }}
                </span>
            </div>

            {{-- Chance of Rain --}}
            <div class="flex items-center gap-3 py-2.5 border-t border-white/8">
                <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-300"
                        fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                    </svg>
                </div>
                <span class="text-sm text-white/60 flex-1">Peluang Hujan</span>
                <span class="text-sm font-semibold text-white">{{ $weather->chanceOfRain }}%</span>
            </div>

            <p class="text-xs text-white/30 mt-2 pt-2 border-t border-white/8">
                Diperbarui: {{ $weather->lastUpdated }}
            </p>
        </div>

    </div>

    {{-- ── Baris 2: Hourly Forecast ── --}}
    <div class="glass-card p-5 weather-enter stagger-2">
        <h3 class="text-xs font-semibold text-white/50 uppercase tracking-widest mb-4">
            ⏱ Prakiraan Per Jam — Hari Ini
        </h3>

        @php $currentHour = (int) now()->format('H'); @endphp

        <div id="hourly-scroll"
             class="flex gap-2 overflow-x-auto scrollbar-thin pb-1">
            @foreach($weather->hourly as $hour)
                <x-hourly-card
                    :hour="$hour"
                    :active="$hour['hour'] === $currentHour"
                />
            @endforeach
        </div>
    </div>

    {{-- ── Baris 3: 7-Day + Astro + AQI ── --}}
    {{--
        Grid responsif:
          < 1024px  : 1 kolom — stacked
          1024–1279 : 2 kolom — 7Day | Astro/AQI berdampingan
          ≥ 1280px  : 3 kolom — 7Day(2/3) | Astro/AQI(1/3)
    --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5">

        {{-- 7-DAY FORECAST (full di lg, 2/3 di xl+) --}}
        <div class="lg:col-span-1 xl:col-span-2 glass-card p-5 weather-enter stagger-3">
            <h3 class="text-xs font-semibold text-white/50 uppercase tracking-widest mb-3">
                📅 Prakiraan 7 Hari
            </h3>
            <div class="divide-y divide-white/5">
                @foreach($weather->forecast as $day)
                    <x-forecast-day :day="$day" :active="$day['active']" />
                @endforeach
            </div>
        </div>

        {{-- ASTRO + AQI (stacked) --}}
        <div class="flex flex-col gap-5">

            {{-- ASTRO CARD --}}
            <div class="glass-card p-5 weather-enter stagger-4">
                <h3 class="text-xs font-semibold text-white/50 uppercase tracking-widest mb-4">
                    🌅 Astronomi
                </h3>

                {{-- Sunrise & Sunset --}}
                <div class="flex items-center justify-between mb-4">
                    <div class="text-center">
                        <div class="text-2xl mb-1">🌅</div>
                        <p class="text-xs text-white/50 mb-0.5">Terbit</p>
                        <p class="text-base font-bold text-white">{{ $weather->sunrise }}</p>
                    </div>

                    {{-- Arc SVG --}}
                    <div class="flex-1 mx-3">
                        <svg viewBox="0 0 100 45" class="w-full" fill="none">
                            <path d="M5 40 Q50 5 95 40" stroke="rgba(255,255,255,0.15)"
                                stroke-width="1.5" stroke-dasharray="3 2"/>
                            <circle cx="50" cy="13" r="5" fill="#fbbf24" opacity="0.8"/>
                            <line x1="5" y1="40" x2="95" y2="40"
                                stroke="rgba(255,255,255,0.1)" stroke-width="1"/>
                        </svg>
                    </div>

                    <div class="text-center">
                        <div class="text-2xl mb-1">🌇</div>
                        <p class="text-xs text-white/50 mb-0.5">Terbenam</p>
                        <p class="text-base font-bold text-white">{{ $weather->sunset }}</p>
                    </div>
                </div>

                {{-- Moon --}}
                <div class="border-t border-white/10 pt-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-white/50 mb-0.5">Fase Bulan</p>
                            <p class="text-sm font-semibold text-white">
                                {{ $weather->moonPhaseIcon() }} {{ $weather->moonPhaseId() }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-white/50 mb-0.5">Terbit / Terbenam</p>
                            <p class="text-xs text-white/70">
                                {{ $weather->moonrise }} / {{ $weather->moonset }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- AQI CARD --}}
            @if($weather->aqiUs !== null)
            <div class="glass-card p-5 weather-enter stagger-5">
                <h3 class="text-xs font-semibold text-white/50 uppercase tracking-widest mb-4">
                    💨 Kualitas Udara
                </h3>

                <div class="flex items-end gap-2 mb-3">
                    <span class="text-4xl font-bold text-white">
                        {{ number_format($weather->aqiUs, 0) }}
                    </span>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold mb-1
                                 {{ $weather->aqiColorClass() }}">
                        {{ $weather->aqiLabel() }}
                    </span>
                </div>

                {{-- AQI color bar --}}
                <div class="relative mb-4">
                    <div class="aqi-bar w-full"></div>
                    {{-- Indikator posisi --}}
                    @php
                        $aqiPercent = min((($weather->aqiUs - 1) / 5) * 100, 100);
                    @endphp
                    <div class="absolute top-1/2 -translate-y-1/2 w-2.5 h-2.5 rounded-full
                                bg-white border-2 border-slate-800 shadow"
                         style="left: calc({{ $aqiPercent }}% - 5px)">
                    </div>
                </div>

                {{-- Polutan --}}
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="glass-card-sm px-2.5 py-2">
                        <p class="text-white/40">PM2.5</p>
                        <p class="font-semibold text-white">{{ number_format($weather->pm25 ?? 0, 1) }} μg</p>
                    </div>
                    <div class="glass-card-sm px-2.5 py-2">
                        <p class="text-white/40">PM10</p>
                        <p class="font-semibold text-white">{{ number_format($weather->pm10 ?? 0, 1) }} μg</p>
                    </div>
                    <div class="glass-card-sm px-2.5 py-2">
                        <p class="text-white/40">O₃</p>
                        <p class="font-semibold text-white">{{ number_format($weather->o3 ?? 0, 1) }} μg</p>
                    </div>
                    <div class="glass-card-sm px-2.5 py-2">
                        <p class="text-white/40">NO₂</p>
                        <p class="font-semibold text-white">{{ number_format($weather->no2 ?? 0, 1) }} μg</p>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>

    @endif {{-- end $weather --}}

    {{-- ===== MY FAVORITE CITIES ===== --}}
    {{-- Hanya tampil untuk user yang sudah login --}}
    @auth
        <x-favorite-list
            :favorites="$favorites"
            :favoritesWeather="$favoritesWeather"
        />
    @endauth

</div>

</x-layouts.app>
