{{--
    Komponen: Favorite List (My Favorite Cities section)
    Menampilkan daftar kota favorit authenticated user beserta data cuaca singkat.

    Props:
    - $favorites        : Collection<FavoriteCity>    — dari DashboardController
    - $favoritesWeather : Collection<string,WeatherData> — key = city name, dari cache

    State:
    - Empty  : belum ada favorit → tampilkan empty state
    - Filled : grid card per kota, masing-masing dengan data cuaca jika tersedia

    Security:
    - Hanya dipanggil dari dalam @auth di dashboard — guest tidak melihat ini
    - Setiap DELETE form hanya mengirim ID favorit, user_id dari Auth::id() di controller
--}}
@props([
    'favorites'        => collect(),
    'favoritesWeather' => collect(),
])

<div class="glass-card p-5 weather-enter stagger-5">

    {{-- ── Header ── --}}
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-xs font-semibold text-white/50 uppercase tracking-widest flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-rose-400"
                fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/>
            </svg>
            My Favorite Cities
        </h3>

        @if($favorites->isNotEmpty())
            <span class="text-xs text-white/40 bg-white/10 px-2 py-0.5 rounded-full">
                {{ $favorites->count() }} kota
            </span>
        @endif
    </div>

    {{-- ── Flash status dari store / destroy ── --}}
    @if(session('favorite_status'))
        @php $fStatus = session('favorite_status'); @endphp
        <div id="fav-flash"
             class="mb-4 px-3.5 py-2.5 rounded-xl text-xs font-medium flex items-center gap-2
                    {{ $fStatus['type'] === 'removed'
                        ? 'bg-slate-500/15 border border-slate-500/25 text-slate-300'
                        : 'bg-rose-500/15 border border-rose-500/25 text-rose-300' }}">
            @if($fStatus['type'] === 'removed')
                {{-- Checkmark untuk removed --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" d="M5 13l4 4L19 7"/>
                </svg>
            @else
                {{-- Heart untuk added / already --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0"
                    fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/>
                </svg>
            @endif
            {{ $fStatus['message'] }}
        </div>
    @endif

    {{-- ── Empty State ── --}}
    @if($favorites->isEmpty())
        <div class="py-10 text-center">
            <div class="w-14 h-14 rounded-2xl bg-white/8 border border-white/10
                        flex items-center justify-center mx-auto mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-white/25" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round"
                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-white/50 mb-1">Belum ada kota favorit</p>
            <p class="text-xs text-white/30 max-w-xs mx-auto">
                Cari kota dan klik "Tambah ke Favorit" untuk menyimpannya di sini.
            </p>
        </div>

    @else
        {{--
            ── Grid Favorite City Cards ──
            Responsif:
              mobile  (< sm) : 1 kolom
              tablet  (sm)   : 2 kolom
              desktop (lg)   : 3 kolom
        --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">

            @foreach($favorites as $fav)
                @php
                    // Ambil data cuaca dari Collection yang sudah di-fetch controller
                    // Key adalah nama kota — null jika API gagal atau kota ke-6+
                    $favWeather = $favoritesWeather->get($fav->city);
                @endphp

                <div class="fav-city-card p-4 flex flex-col gap-3 group relative">

                    {{-- ── Header card: lokasi ── --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 text-white/40 text-xs mb-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 flex-shrink-0"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                                    aria-hidden="true">
                                    <path stroke-linecap="round"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="truncate">{{ $fav->country ?? '' }}</span>
                            </div>

                            {{-- Nama kota sebagai link ke pencarian ── --}}
                            <a href="{{ route('dashboard', ['city' => $fav->city]) }}"
                               aria-label="Lihat cuaca {{ $fav->city }}"
                               class="text-sm font-semibold text-white/90 hover:text-white
                                      transition-colors truncate block">
                                {{ $fav->city }}
                            </a>
                        </div>

                        {{-- Ikon cuaca (jika data tersedia) ── --}}
                        @if($favWeather)
                            <img src="{{ $favWeather->conditionIcon }}"
                                 alt="{{ $favWeather->conditionText }}"
                                 class="fav-weather-icon w-10 h-10 object-contain flex-shrink-0"
                                 loading="lazy">
                        @else
                            {{-- Placeholder ikon jika API gagal / kota ke-6+ --}}
                            <div class="w-10 h-10 rounded-lg bg-white/8 flex-shrink-0
                                        flex items-center justify-center" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white/20"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <circle cx="12" cy="12" r="4"/>
                                    <path stroke-linecap="round" d="M12 2v2m0 16v2M4.22 4.22l1.42 1.42m12.72 12.72 1.42 1.42M2 12h2m16 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                                </svg>
                            </div>
                        @endif
                    </div>

                    {{-- ── Data cuaca singkat ── --}}
                    @if($favWeather)
                        <div class="flex items-end justify-between">
                            <div>
                                {{-- Suhu --}}
                                <div class="flex items-start gap-0.5 leading-none">
                                    <span class="text-2xl font-thin text-white">
                                        {{ number_format($favWeather->tempC, 0) }}
                                    </span>
                                    <span class="text-sm text-white/50 mt-0.5">°C</span>
                                </div>
                                {{-- Feels like --}}
                                <p class="text-xs text-white/40 mt-0.5">
                                    Terasa {{ number_format($favWeather->feelsLikeC, 0) }}°
                                </p>
                            </div>

                            <div class="text-right">
                                {{-- Kondisi singkat --}}
                                <p class="text-xs text-white/60 truncate max-w-24">
                                    {{ $favWeather->conditionText }}
                                </p>
                                {{-- Badge siang/malam --}}
                                <span class="text-xs {{ $favWeather->isDay ? 'text-amber-300/70' : 'text-indigo-300/70' }}">
                                    {{ $favWeather->isDay ? '☀️' : '🌙' }}
                                </span>
                            </div>
                        </div>
                    @else
                        {{-- Placeholder teks jika cuaca tidak tersedia ── --}}
                        <p class="text-xs text-white/30 italic">
                            Data cuaca tidak tersedia
                        </p>
                    @endif

                    {{-- ── Footer: tombol Remove (selalu tampil di mobile, hover di desktop) ── --}}
                    <div class="pt-2 border-t border-white/8 flex items-center justify-between">
                        {{-- Link ke search ulang --}}
                        <a href="{{ route('dashboard', ['city' => $fav->city]) }}"
                           aria-label="Cari cuaca {{ $fav->city }}"
                           class="text-xs text-white/40 hover:text-white/70 transition-colors">
                            Lihat detail →
                        </a>

                        {{-- Tombol Remove ── --}}
                        <form action="{{ route('favorites.destroy', $fav->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                aria-label="Hapus {{ $fav->city }} dari favorit"
                                title="Hapus {{ $fav->city }} dari favorit"
                                data-fav-remove
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs
                                       font-medium text-rose-300/70 hover:text-rose-300
                                       hover:bg-rose-500/20 border border-transparent
                                       hover:border-rose-400/30 transition-all duration-150
                                       focus:outline-none focus-visible:ring-1 focus-visible:ring-rose-400/50
                                       sm:opacity-0 sm:group-hover:opacity-100 sm:focus-visible:opacity-100"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3"
                                    fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/>
                                </svg>
                                Hapus
                            </button>
                        </form>
                    </div>

                </div>
            @endforeach

        </div>

        {{-- Catatan jika ada kota ke-6+ yang tidak ditampilkan cuacanya ── --}}
        @if($favorites->count() > 5)
            <p class="mt-3 text-xs text-white/30 text-center">
                Menampilkan cuaca untuk 5 kota pertama.
                {{ $favorites->count() - 5 }} kota lainnya tetap tersimpan.
            </p>
        @endif

    @endif

</div>
