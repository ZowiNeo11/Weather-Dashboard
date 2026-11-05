{{--
    Komponen: Favorite Button
    Menampilkan tombol Add to Favorites / Favorited untuk kota yang sedang dilihat.

    Props:
    - $city           : string        — nama kota dari WeatherData
    - $country        : string|null   — nama negara dari WeatherData
    - $isFavorited    : bool          — apakah kota ini sudah difavoritkan user
    - $favoriteRecord : ?FavoriteCity — model record jika sudah favorit (butuh id untuk DELETE)

    Logika tampil:
    - Guest      → CTA login (bukan tombol fungsional)
    - Auth, belum favorit → tombol "Tambah ke Favorit" (POST /favorites)
    - Auth, sudah favorit → tombol "Favorited" (DELETE /favorites/{id})

    Security:
    - @csrf pada setiap form
    - @method('DELETE') untuk route destroy
    - Tidak ada user_id di form — controller mengambil dari Auth::id()
    - data-fav-submit untuk JS: prevent double-submit
--}}
@props([
    'city'           => '',
    'country'        => null,
    'isFavorited'    => false,
    'favoriteRecord' => null,
])

@auth
    @if(! $isFavorited)
        {{-- ── Belum difavoritkan: tombol Add ── --}}
        <form action="{{ route('favorites.store') }}" method="POST" data-fav-form>
            @csrf
            <input type="hidden" name="city"    value="{{ $city }}">
            <input type="hidden" name="country" value="{{ $country }}">

            <button
                type="submit"
                aria-label="Tambahkan {{ $city }} ke favorit"
                title="Tambahkan {{ $city }} ke favorit"
                data-fav-submit
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl
                       bg-white/10 hover:bg-rose-500/20 border border-white/15
                       hover:border-rose-400/40 text-white/70 hover:text-rose-300
                       text-sm font-medium transition-all duration-200
                       focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/50
                       disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{-- Heart outline — belum favorit --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round"
                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                </svg>
                Tambah ke Favorit
            </button>
        </form>

    @else
        {{-- ── Sudah difavoritkan: tombol Remove ── --}}
        <form action="{{ route('favorites.destroy', $favoriteRecord->id) }}" method="POST"
              data-fav-form>
            @csrf
            @method('DELETE')

            <button
                type="submit"
                aria-label="Hapus {{ $city }} dari favorit"
                title="Hapus {{ $city }} dari favorit"
                data-fav-submit
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl
                       bg-rose-500/20 hover:bg-rose-500/30 border border-rose-400/40
                       hover:border-rose-400/60 text-rose-300 hover:text-rose-200
                       text-sm font-medium transition-all duration-200
                       focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/50
                       disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{-- Heart filled — sudah favorit --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor"
                    viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/>
                </svg>
                Favorited
            </button>
        </form>
    @endif

@endauth

@guest
    {{--
        Guest: CTA untuk login.
        Bukan tombol fungsional — tidak bisa menambah favorit.
        Pointer ke halaman login yang sudah ada.
    --}}
    <a href="{{ route('auth.login') }}"
       aria-label="Masuk untuk menambahkan {{ $city }} ke favorit"
       title="Masuk untuk menambahkan {{ $city }} ke favorit"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl
              bg-white/5 border border-white/10 text-white/40
              text-sm font-medium transition-colors
              hover:bg-white/10 hover:text-white/60
              focus:outline-none focus-visible:ring-2 focus-visible:ring-white/20">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
            aria-hidden="true">
            <path stroke-linecap="round"
                d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
        </svg>
        Masuk untuk menyimpan favorit
    </a>
@endguest
