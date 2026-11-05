<?php

use App\Models\FavoriteCity;
use App\Models\SearchHistory;
use App\Models\User;
use App\Services\WeatherService;

// =========================================================
// PHASE D — STEP 3: Search History Retrieval Logic Tests
// =========================================================

// ─────────────────────────────────────────────────────────────
// SKENARIO 1: User A dapat mengambil history miliknya
// ─────────────────────────────────────────────────────────────
test('User A dapat mengambil search history miliknya', function () {
    $userA = User::factory()->create();

    SearchHistory::create(['user_id' => $userA->id, 'city' => 'Bandung',  'country' => 'Indonesia', 'searched_at' => now()->subMinutes(5)]);
    SearchHistory::create(['user_id' => $userA->id, 'city' => 'Jakarta',  'country' => 'Indonesia', 'searched_at' => now()->subMinutes(3)]);
    SearchHistory::create(['user_id' => $userA->id, 'city' => 'Surabaya', 'country' => 'Indonesia', 'searched_at' => now()->subMinutes(1)]);

    $histories = SearchHistory::forUser($userA->id)->get();

    expect($histories)->toHaveCount(3);
    expect($histories->pluck('city')->toArray())->toContain('Bandung', 'Jakarta', 'Surabaya');
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 2: User B hanya mendapatkan history miliknya
// ─────────────────────────────────────────────────────────────
test('User B hanya mendapatkan history miliknya bukan history User A', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    SearchHistory::create(['user_id' => $userA->id, 'city' => 'Bandung', 'searched_at' => now()]);
    SearchHistory::create(['user_id' => $userA->id, 'city' => 'Jakarta', 'searched_at' => now()]);
    SearchHistory::create(['user_id' => $userB->id, 'city' => 'Bali',    'searched_at' => now()]);

    $historiesB = SearchHistory::forUser($userB->id)->get();

    // User B hanya dapat 1 record miliknya
    expect($historiesB)->toHaveCount(1);
    expect($historiesB->first()->city)->toBe('Bali');

    // User B tidak melihat record User A
    expect($historiesB->pluck('city')->contains('Bandung'))->toBeFalse();
    expect($historiesB->pluck('city')->contains('Jakarta'))->toBeFalse();
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 3: User A tidak mendapatkan history User B
// ─────────────────────────────────────────────────────────────
test('User A tidak mendapatkan history User B', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    SearchHistory::create(['user_id' => $userA->id, 'city' => 'Solo',    'searched_at' => now()]);
    SearchHistory::create(['user_id' => $userB->id, 'city' => 'Malang',  'searched_at' => now()]);
    SearchHistory::create(['user_id' => $userB->id, 'city' => 'Makassar','searched_at' => now()]);

    $historiesA = SearchHistory::forUser($userA->id)->get();

    expect($historiesA)->toHaveCount(1);
    expect($historiesA->first()->city)->toBe('Solo');
    expect($historiesA->pluck('city')->contains('Malang'))->toBeFalse();
    expect($historiesA->pluck('city')->contains('Makassar'))->toBeFalse();
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 4: History diurutkan dari terbaru ke terlama
// ─────────────────────────────────────────────────────────────
test('search history diurutkan dari searched_at terbaru ke terlama', function () {
    $user = User::factory()->create();

    SearchHistory::create(['user_id' => $user->id, 'city' => 'Pertama', 'searched_at' => now()->subMinutes(30)]);
    SearchHistory::create(['user_id' => $user->id, 'city' => 'Kedua',   'searched_at' => now()->subMinutes(15)]);
    SearchHistory::create(['user_id' => $user->id, 'city' => 'Ketiga',  'searched_at' => now()->subMinutes(5)]);
    SearchHistory::create(['user_id' => $user->id, 'city' => 'Keempat', 'searched_at' => now()]);

    $histories = SearchHistory::forUser($user->id)->get();

    // Urutan: terbaru di atas
    expect($histories->first()->city)->toBe('Keempat');
    expect($histories->last()->city)->toBe('Pertama');

    // Verifikasi urutan penuh
    $cities = $histories->pluck('city')->toArray();
    expect($cities)->toBe(['Keempat', 'Ketiga', 'Kedua', 'Pertama']);
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 4b: Tiebreaker — id DESC jika searched_at sama
// ─────────────────────────────────────────────────────────────
test('tiebreaker id DESC digunakan jika searched_at identik', function () {
    $user = User::factory()->create();
    $ts   = now()->setMilliseconds(0); // timestamp sama persis

    $h1 = SearchHistory::create(['user_id' => $user->id, 'city' => 'Kota1', 'searched_at' => $ts]);
    $h2 = SearchHistory::create(['user_id' => $user->id, 'city' => 'Kota2', 'searched_at' => $ts]);
    $h3 = SearchHistory::create(['user_id' => $user->id, 'city' => 'Kota3', 'searched_at' => $ts]);

    $histories = SearchHistory::forUser($user->id)->get();

    // ID lebih besar (lebih baru dibuat) → di atas
    expect($histories->first()->id)->toBe($h3->id);
    expect($histories->last()->id)->toBe($h1->id);
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 5: Duplicate city tetap tampil sebagai record terpisah
// ─────────────────────────────────────────────────────────────
test('duplicate city tampil sebagai record terpisah dalam search history', function () {
    $user = User::factory()->create();

    SearchHistory::create(['user_id' => $user->id, 'city' => 'Bandung', 'searched_at' => now()->subMinutes(20)]);
    SearchHistory::create(['user_id' => $user->id, 'city' => 'Jakarta', 'searched_at' => now()->subMinutes(10)]);
    SearchHistory::create(['user_id' => $user->id, 'city' => 'Bandung', 'searched_at' => now()->subMinutes(2)]);

    $histories = SearchHistory::forUser($user->id)->get();

    // Tiga record — Bandung muncul dua kali
    expect($histories)->toHaveCount(3);
    expect($histories->where('city', 'Bandung')->count())->toBe(2);

    // Bandung terbaru di atas, Jakarta di tengah, Bandung lama di bawah
    expect($histories->first()->city)->toBe('Bandung');
    expect($histories->get(1)->city)->toBe('Jakarta');
    expect($histories->last()->city)->toBe('Bandung');
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 6: User tanpa history mendapat empty collection
// ─────────────────────────────────────────────────────────────
test('user tanpa search history mendapat empty collection bukan error', function () {
    $user = User::factory()->create();

    $histories = SearchHistory::forUser($user->id)->get();

    expect($histories)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($histories)->toHaveCount(0);
    expect($histories->isEmpty())->toBeTrue();
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 7: Guest tidak dapat mengakses protected history
// ─────────────────────────────────────────────────────────────
test('guest tidak dapat mengakses protected history retrieval route jika ada', function () {
    // Dashboard untuk guest harus tetap 200 dengan $searchHistories = collect()
    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);

    // View menerima searchHistories sebagai empty collection untuk guest
    $response->assertViewHas('searchHistories', function ($histories) {
        return $histories->isEmpty();
    });
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 8: Authenticated user mendapat $searchHistories di view
// ─────────────────────────────────────────────────────────────
test('authenticated user mendapat searchHistories di view', function () {
    $user = User::factory()->create();

    SearchHistory::create(['user_id' => $user->id, 'city' => 'Yogyakarta', 'searched_at' => now()]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertViewHas('searchHistories', function ($histories) {
        return $histories->isNotEmpty();
    });
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 8b: $searchHistories hanya berisi milik user yang login
// ─────────────────────────────────────────────────────────────
test('searchHistories di view hanya berisi history milik authenticated user', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    SearchHistory::create(['user_id' => $userA->id, 'city' => 'MedanA', 'searched_at' => now()]);
    SearchHistory::create(['user_id' => $userB->id, 'city' => 'MedanB', 'searched_at' => now()]);

    // User A login
    $response = $this->actingAs($userA)->get(route('dashboard'));
    $response->assertViewHas('searchHistories', function ($histories) use ($userA) {
        // Semua record milik User A
        return $histories->every(fn ($h) => $h->user_id === $userA->id)
            && $histories->pluck('city')->contains('MedanA')
            && ! $histories->pluck('city')->contains('MedanB');
    });
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 9: Selected columns — tidak ada kolom sensitif
// ─────────────────────────────────────────────────────────────
test('result hanya mengandung kolom yang diperlukan bukan data sensitif', function () {
    $user = User::factory()->create();

    SearchHistory::create(['user_id' => $user->id, 'city' => 'Pontianak', 'searched_at' => now()]);

    $histories = SearchHistory::forUser($user->id)->get();
    $record    = $histories->first();

    // Kolom yang harus ada
    expect($record->id)->not->toBeNull();
    expect($record->city)->toBe('Pontianak');
    expect($record->searched_at)->not->toBeNull();

    // updated_at tidak di-select (tidak diperlukan) — akan null
    // Cukup pastikan password/api_key tidak ada
    $keys = array_keys($record->toArray());
    expect($keys)->not->toContain('password');
    expect($keys)->not->toContain('api_key');
    expect($keys)->not->toContain('weather_json');
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 10: Existing Search History recording tetap bekerja
// ─────────────────────────────────────────────────────────────
test('recording dan retrieval bekerja bersama secara konsisten', function () {
    $user = User::factory()->create();

    // Buat beberapa record secara langsung
    SearchHistory::create(['user_id' => $user->id, 'city' => 'Palembang', 'searched_at' => now()->subMinutes(10)]);
    SearchHistory::create(['user_id' => $user->id, 'city' => 'Manado',    'searched_at' => now()->subMinutes(5)]);

    // Retrieval harus mengembalikan keduanya, urut terbaru di atas
    $histories = SearchHistory::forUser($user->id)->get();

    expect($histories)->toHaveCount(2);
    expect($histories->first()->city)->toBe('Manado');
    expect($histories->last()->city)->toBe('Palembang');
});

// ─────────────────────────────────────────────────────────────
// REGRESSION: Favorite Cities tetap bekerja
// ─────────────────────────────────────────────────────────────
test('Favorite Cities tidak terpengaruh oleh Search History retrieval', function () {
    $user = User::factory()->create();

    FavoriteCity::create(['user_id' => $user->id, 'city' => 'Ambon', 'country' => 'Indonesia']);
    SearchHistory::create(['user_id' => $user->id, 'city' => 'Ambon', 'searched_at' => now()]);

    $favorites  = \App\Models\FavoriteCity::forUser($user->id)->get();
    $histories  = SearchHistory::forUser($user->id)->get();

    // Keduanya independen
    expect($favorites)->toHaveCount(1);
    expect($histories)->toHaveCount(1);
    expect($favorites->first())->toBeInstanceOf(FavoriteCity::class);
    expect($histories->first())->toBeInstanceOf(SearchHistory::class);
});

// ─────────────────────────────────────────────────────────────
// REGRESSION: Weather Search tetap bekerja
// ─────────────────────────────────────────────────────────────
test('weather search tetap bekerja dan $searchHistories dikirim ke view', function () {
    $user = User::factory()->create();

    $mock = Mockery::mock(\App\Services\WeatherService::class);
    $mock->shouldReceive('getWeatherWithForecast')
         ->with('Balikpapan')
         ->once()
         ->andReturn(new \App\DTOs\WeatherData(
             city: 'Balikpapan', region: 'East Kalimantan', country: 'Indonesia',
             localTime: '2026-01-01 10:00', lat: -1.2, lon: 116.8, timezone: 'Asia/Jakarta',
             tempC: 30.0, feelsLikeC: 33.0, conditionText: 'Sunny',
             conditionIcon: 'https://cdn.example.com/icon.png', conditionCode: 1000,
             humidity: 65, windKph: 8.0, windDir: 'N', windDegree: 0,
             pressureMb: 1010.0, visibilityKm: 10.0, uvIndex: 6.0, precipMm: 0.0, cloud: 10,
             lastUpdated: '2026-01-01 09:45', isDay: true,
             sunrise: '06:00 AM', sunset: '06:00 PM', moonPhase: 'Full Moon',
             moonrise: '06:30 PM', moonset: '06:30 AM',
             aqiUs: null, pm25: null, pm10: null, co: null, o3: null, no2: null,
             forecast: collect(), hourly: collect(), chanceOfRain: 5,
         ));
    $mock->shouldReceive('getRawForecastData')->never();
    $this->app->instance(\App\Services\WeatherService::class, $mock);

    $response = $this->actingAs($user)->get(route('dashboard', ['city' => 'Balikpapan']));

    $response->assertStatus(200);
    $response->assertViewHas('weather');
    $response->assertViewHas('searchHistories');
    $response->assertViewHas('favorites');

    // Search history juga direcord
    expect(SearchHistory::where('user_id', $user->id)->where('city', 'Balikpapan')->count())->toBe(1);
});
