<?php

namespace App\Http\Controllers;

use App\DTOs\WeatherData;
use App\Exceptions\WeatherException;
use App\Models\FavoriteCity;
use App\Models\SearchHistory;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * DashboardController
 *
 * Tanggung jawab controller ini HANYA:
 *   1. Menerima HTTP request
 *   2. Mendelegasikan ke WeatherService
 *   3. Meneruskan data ke View
 *
 * Tidak ada logika bisnis atau HTTP call di sini.
 * WeatherService di-inject via constructor agar mudah di-mock saat testing.
 */
class DashboardController extends Controller
{
    /**
     * Timezone kanonik untuk Bandung / WIB.
     * Didefinisikan sebagai konstanta agar konsisten — satu titik perubahan
     * jika suatu saat perlu diganti.
     */
    private const TIMEZONE_WIB = 'Asia/Jakarta';

    public function __construct(
        private readonly WeatherService $weatherService,
    ) {}

    /**
     * Tampilkan halaman dashboard.
     */
    public function index(Request $request): View
    {
        $city     = $request->query('city');
        $weather  = null;
        $error    = null;
        $lastCity = $city;

        if ($city) {
            try {
                $weather = $this->weatherService->getWeatherWithForecast($city);
            } catch (WeatherException $e) {
                $error = $e->getMessage();
            }
        }

        $fallbackTheme = $this->resolveFallbackTheme();

        // --- Record search history (hanya untuk authenticated user + successful search) ---
        // Dilakukan di sini karena:
        //   1. $weather sudah confirmed valid (WeatherData object, bukan null)
        //   2. city & country tersedia dari WeatherData — tidak butuh request API tambahan
        //   3. Jika gagal menyimpan, weather result tetap ditampilkan (error isolation)
        if ($weather && Auth::check()) {
            $this->recordSearchHistory($weather);
        }

        // --- Favorite Cities data (hanya untuk authenticated user) ---
        // isFavorited : apakah kota yang sedang ditampilkan sudah difavoritkan user ini
        // favorites   : semua kota favorit user untuk ditampilkan di section "My Favorites"
        //
        // Keduanya bernilai null/collection kosong untuk guest agar Blade tidak perlu
        // menangani kondisi auth secara manual — cukup @auth di view.
        $isFavorited      = false;
        $favoriteRecord   = null; // FavoriteCity model jika kota ini sudah difavoritkan
        $favorites        = collect();
        $favoritesWeather = collect(); // WeatherData per kota favorit (dari cache)
        $searchHistories  = collect(); // SearchHistory milik authenticated user

        if (Auth::check()) {
            $userId = Auth::id();

            // Gunakan scopeForUser() dari FavoriteCity model — satu titik definisi query
            $favorites = FavoriteCity::forUser($userId)->get();

            // Cek isFavorited dari Collection yang sudah diambil — tidak perlu query baru
            if ($weather) {
                $favoriteRecord = $favorites->firstWhere('city', $weather->city);
                $isFavorited    = $favoriteRecord !== null;
            }

            // Ambil data cuaca per kota favorit (maks 5) menggunakan cache.
            // WeatherService digunakan langsung — tidak ada duplikasi API logic.
            // Cache TTL 30 menit per kota agar tidak ada N API calls per request.
            $favoritesWeather = $this->fetchFavoritesWeather(
                $favorites->take(5),
            );

            // Ambil search history milik user — di-scope ke user ini saja.
            // Menggunakan scopeForUser() yang sudah ada di model:
            //   urut searched_at DESC, id DESC (tiebreaker), select kolom minimal.
            // Guest mendapat collect() kosong (default di atas) — tidak ada query.
            $searchHistories = SearchHistory::forUser($userId)->get();
        }

        return view('dashboard', compact(
            'weather',
            'error',
            'lastCity',
            'fallbackTheme',
            'isFavorited',
            'favoriteRecord',
            'favorites',
            'favoritesWeather',
            'searchHistories',
        ));
    }

    /**
     * Tentukan tema fallback berdasarkan jam saat ini di timezone Asia/Jakarta.
     * Dipanggil hanya untuk state tanpa data cuaca (empty/error).
     */
    private function resolveFallbackTheme(): string
    {
        // Carbon::now() dengan timezone eksplisit — tidak terpengaruh .env APP_TIMEZONE
        // maupun timezone yang di-set di OS Windows.
        $hourWib = Carbon::now(self::TIMEZONE_WIB)->hour;

        // Siang: 06:00–17:59 WIB
        return ($hourWib >= 6 && $hourWib < 18)
            ? 'theme-sunny'
            : 'theme-night';
    }

    /**
     * Catat pencarian kota ke tabel search_histories dan terapkan retention policy.
     *
     * Flow:
     *   1. INSERT record baru dalam satu DB transaction bersama pruning
     *   2. SearchHistory::pruneForUser() menghapus record lama melebihi limit
     *
     * Transaction memastikan insert + prune atomic — tidak ada state di mana
     * record sudah ada tapi pruning belum jalan (atau sebaliknya).
     *
     * Error isolation:
     *   Exception di-catch, di-log, dan TIDAK di-rethrow — weather result
     *   tetap ditampilkan meskipun history gagal disimpan.
     */
    private function recordSearchHistory(WeatherData $weather): void
    {
        $userId = Auth::id();

        try {
            DB::transaction(function () use ($userId, $weather): void {
                SearchHistory::create([
                    'user_id'     => $userId,
                    'city'        => $weather->city,
                    'country'     => $weather->country ?: null,
                    'searched_at' => now(),
                ]);

                // Terapkan retention policy — hapus record lama jika melebihi limit.
                // Limit dibaca dari config('app.search_history_limit') — satu titik konfigurasi.
                SearchHistory::pruneForUser($userId);
            });
        } catch (\Throwable $e) {
            // Log agar bisa diidentifikasi saat debugging — tidak di-rethrow
            Log::error('SearchHistory: Gagal menyimpan riwayat pencarian', [
                'user_id' => $userId,
                'city'    => $weather->city,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Ambil data cuaca untuk setiap kota favorit menggunakan cache.
     *
     * Desain:
     * - Cache per kota (key: "weather_fav_{city}") dengan TTL 30 menit
     * - PENTING: yang dicache adalah RAW ARRAY dari API response, BUKAN WeatherData object.
     *   Menyimpan PHP object ke database cache berbahaya karena jika class WeatherData
     *   berubah, cache lama mengandung serialized object yang tidak kompatibel →
     *   "incomplete object" error saat unserialize.
     *   Array adalah serializable secara stabil dan tidak bergantung pada class definition.
     * - WeatherData di-reconstruct SETELAH data keluar dari cache
     * - Jika API gagal untuk satu kota, kota lain tetap tampil (silent skip)
     *
     * @param  Collection<int, FavoriteCity> $favorites
     * @return Collection<string, WeatherData>
     */
    private function fetchFavoritesWeather(Collection $favorites): Collection
    {
        if ($favorites->isEmpty()) {
            return collect();
        }

        return $favorites
            ->mapWithKeys(function (FavoriteCity $fav): array {
                // Cache key dikelola oleh model — domain knowledge tentang FavoriteCity
                try {
                    // Cache menyimpan ARRAY (raw API response), bukan WeatherData object.
                    // Array adalah format yang aman untuk serialisasi ke database cache.
                    $rawData = Cache::remember(
                        $fav->weatherCacheKey(),
                        now()->addMinutes(30),
                        fn () => $this->weatherService->getRawForecastData($fav->city),
                    );

                    // Reconstruct WeatherData dari array setelah keluar dari cache —
                    // ini memastikan WeatherData selalu dibuat dari class definition terkini
                    $data = WeatherData::fromForecastResponse($rawData);

                    return [$fav->city => $data];

                } catch (WeatherException) {
                    // Jika API gagal untuk kota ini, skip saja — jangan crash seluruh dashboard
                    return [];
                } catch (\Throwable) {
                    // Tangkap error lain (termasuk malformed cache dari versi lama)
                    // Cache entry akan di-overwrite pada request berikutnya
                    Cache::forget($fav->weatherCacheKey());
                    return [];
                }
            })
            ->filter(); // Hapus entry kosong dari API yang gagal
    }

    /**
     * Proses form pencarian kota (POST → redirect GET / PRG Pattern).
     */
    public function search(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'city' => ['required', 'string', 'min:2', 'max:100'],
        ], [
            'city.required' => 'Nama kota wajib diisi.',
            'city.min'      => 'Nama kota minimal 2 karakter.',
            'city.max'      => 'Nama kota terlalu panjang (maks 100 karakter).',
        ]);

        return redirect()->route('dashboard', [
            'city' => trim($validated['city']),
        ]);
    }
}
