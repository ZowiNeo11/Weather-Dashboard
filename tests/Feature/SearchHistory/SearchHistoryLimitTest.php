<?php

use App\Models\SearchHistory;
use App\Models\User;
use App\Services\WeatherService;
use Illuminate\Support\Facades\Config;

// =========================================================
// PHASE D — STEP 4: Search History Limit & Management Tests
// =========================================================

/**
 * Helper: isi $count record riwayat untuk user, terbaru terakhir.
 * searched_at diberi offset agar urutan deterministic.
 */
function seedHistory(User $user, int $count): void
{
    for ($i = $count; $i >= 1; $i--) {
        SearchHistory::create([
            'user_id'     => $user->id,
            'city'        => "City{$i}",
            'country'     => 'Indonesia',
            'searched_at' => now()->subMinutes($i),
        ]);
    }
}

// ─────────────────────────────────────────────────────────────
// SKENARIO 1: Kurang dari 20 — semua record dipertahankan
// ─────────────────────────────────────────────────────────────
test('user dengan kurang dari 20 record mempertahankan semua record', function () {
    $user = User::factory()->create();
    seedHistory($user, 15);

    // Panggil prune secara manual — simulasi after-insert
    SearchHistory::pruneForUser($user->id);

    expect(SearchHistory::where('user_id', $user->id)->count())->toBe(15);
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 2: Tepat 20 — semua record dipertahankan
// ─────────────────────────────────────────────────────────────
test('user dengan tepat 20 record mempertahankan semua record', function () {
    $user = User::factory()->create();
    seedHistory($user, 20);

    SearchHistory::pruneForUser($user->id);

    expect(SearchHistory::where('user_id', $user->id)->count())->toBe(20);
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 3: 21 record — hanya 20 terbaru dipertahankan
// ─────────────────────────────────────────────────────────────
test('user dengan 21 record mempertahankan hanya 20 record terbaru', function () {
    $user = User::factory()->create();
    seedHistory($user, 21); // City21 paling lama, City1 paling baru

    SearchHistory::pruneForUser($user->id);

    expect(SearchHistory::where('user_id', $user->id)->count())->toBe(20);

    // City21 adalah yang paling lama — harus dihapus
    expect(SearchHistory::where('user_id', $user->id)->where('city', 'City21')->exists())->toBeFalse();

    // City1 adalah yang paling baru — harus ada
    expect(SearchHistory::where('user_id', $user->id)->where('city', 'City1')->exists())->toBeTrue();
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 4: 30 record — hanya 20 terbaru dipertahankan
// ─────────────────────────────────────────────────────────────
test('user dengan 30 record mempertahankan hanya 20 record terbaru', function () {
    $user = User::factory()->create();
    seedHistory($user, 30);

    SearchHistory::pruneForUser($user->id);

    $remaining = SearchHistory::where('user_id', $user->id)->count();
    expect($remaining)->toBe(20);

    // Kota yang dipangkas (City21–City30 adalah yang paling lama)
    foreach (range(21, 30) as $i) {
        expect(SearchHistory::where('user_id', $user->id)->where('city', "City{$i}")->exists())->toBeFalse();
    }

    // Kota yang dipertahankan (City1–City20 adalah yang paling baru)
    foreach (range(1, 20) as $i) {
        expect(SearchHistory::where('user_id', $user->id)->where('city', "City{$i}")->exists())->toBeTrue();
    }
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 5: Duplicate city — setiap entry dihitung terpisah
// ─────────────────────────────────────────────────────────────
test('duplicate city dihitung sebagai record terpisah dalam limit', function () {
    $user = User::factory()->create();

    // 19 kota unik + 2 entri Bandung = 21 total
    for ($i = 1; $i <= 19; $i++) {
        SearchHistory::create([
            'user_id'     => $user->id,
            'city'        => "Kota{$i}",
            'searched_at' => now()->subMinutes(21 - $i), // terbaru = Kota19
        ]);
    }
    // Bandung lama (dihapus saat prune)
    $oldBandung = SearchHistory::create([
        'user_id'     => $user->id,
        'city'        => 'Bandung',
        'searched_at' => now()->subMinutes(25), // lebih lama dari semua
    ]);
    // Bandung baru (dipertahankan)
    SearchHistory::create([
        'user_id'     => $user->id,
        'city'        => 'Bandung',
        'searched_at' => now(), // paling baru
    ]);

    SearchHistory::pruneForUser($user->id);

    expect(SearchHistory::where('user_id', $user->id)->count())->toBe(20);

    // Bandung lama harus terhapus
    expect(SearchHistory::where('id', $oldBandung->id)->exists())->toBeFalse();

    // Bandung baru harus ada
    expect(
        SearchHistory::where('user_id', $user->id)
            ->where('city', 'Bandung')
            ->orderByDesc('searched_at')
            ->exists()
    )->toBeTrue();
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 6: Record terbaru tidak pernah dihapus
// ─────────────────────────────────────────────────────────────
test('record terbaru tidak pernah dihapus oleh retention policy', function () {
    $user = User::factory()->create();
    seedHistory($user, 25);

    // Tambah record paling baru
    $newest = SearchHistory::create([
        'user_id'     => $user->id,
        'city'        => 'PalingBaru',
        'searched_at' => now()->addMinute(), // lebih baru dari semua
    ]);

    SearchHistory::pruneForUser($user->id);

    // Record paling baru harus tetap ada
    expect(SearchHistory::where('id', $newest->id)->exists())->toBeTrue();
    expect(SearchHistory::where('user_id', $user->id)->count())->toBe(20);
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 7: Retention hanya mempengaruhi user yang bersangkutan
// ─────────────────────────────────────────────────────────────
test('pruneForUser hanya mempengaruhi record milik user yang bersangkutan', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    // User A: 25 record (akan dipangkas ke 20)
    seedHistory($userA, 25);

    // User B: 5 record (tidak boleh berkurang)
    for ($i = 1; $i <= 5; $i++) {
        SearchHistory::create([
            'user_id'     => $userB->id,
            'city'        => "BKota{$i}",
            'searched_at' => now()->subMinutes($i),
        ]);
    }

    // Prune hanya untuk User A
    SearchHistory::pruneForUser($userA->id);

    expect(SearchHistory::where('user_id', $userA->id)->count())->toBe(20);
    expect(SearchHistory::where('user_id', $userB->id)->count())->toBe(5); // tidak berubah
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 8: Record user lain tidak tersentuh
// ─────────────────────────────────────────────────────────────
test('record user lain tidak ikut terhapus saat pruning user tertentu', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $userC = User::factory()->create();

    seedHistory($userA, 30); // akan dipangkas
    seedHistory($userB, 10); // tidak berubah
    seedHistory($userC, 8);  // tidak berubah

    SearchHistory::pruneForUser($userA->id);

    expect(SearchHistory::where('user_id', $userA->id)->count())->toBe(20);
    expect(SearchHistory::where('user_id', $userB->id)->count())->toBe(10);
    expect(SearchHistory::where('user_id', $userC->id)->count())->toBe(8);
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 9: Guest tidak memicu retention
// ─────────────────────────────────────────────────────────────
test('guest search tidak memicu retention karena tidak ada recording', function () {
    // Tidak ada user yang login — tidak ada recording, tidak ada pruning
    $totalBefore = SearchHistory::count();

    $mock = Mockery::mock(WeatherService::class);
    $mock->shouldReceive('getWeatherWithForecast')
         ->andReturn(new \App\DTOs\WeatherData(
             city: 'London', region: 'England', country: 'United Kingdom',
             localTime: '2026-01-01 10:00', lat: 51.5, lon: -0.1, timezone: 'Europe/London',
             tempC: 15.0, feelsLikeC: 13.0, conditionText: 'Cloudy',
             conditionIcon: 'https://cdn.example.com/icon.png', conditionCode: 1006,
             humidity: 80, windKph: 20.0, windDir: 'W', windDegree: 270,
             pressureMb: 1015.0, visibilityKm: 10.0, uvIndex: 2.0, precipMm: 0.0, cloud: 75,
             lastUpdated: '2026-01-01 09:45', isDay: true,
             sunrise: '07:00 AM', sunset: '05:00 PM', moonPhase: 'Full Moon',
             moonrise: '06:30 PM', moonset: '06:30 AM',
             aqiUs: null, pm25: null, pm10: null, co: null, o3: null, no2: null,
             forecast: collect(), hourly: collect(), chanceOfRain: 30,
         ));
    $mock->shouldReceive('getRawForecastData')->never();
    $this->app->instance(WeatherService::class, $mock);

    $this->get(route('dashboard', ['city' => 'London']))->assertStatus(200);

    expect(SearchHistory::count())->toBe($totalBefore);
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 10: Pencarian gagal tidak membuat history
// ─────────────────────────────────────────────────────────────
test('pencarian gagal tidak membuat history dan tidak memicu retention', function () {
    $user = User::factory()->create();

    $mock = Mockery::mock(WeatherService::class);
    $mock->shouldReceive('getWeatherWithForecast')
         ->andThrow(\App\Exceptions\WeatherException::cityNotFound('KotaGagal'));
    $mock->shouldReceive('getRawForecastData')->never();
    $this->app->instance(WeatherService::class, $mock);

    $this->actingAs($user)->get(route('dashboard', ['city' => 'KotaGagal']))->assertStatus(200);

    expect(SearchHistory::where('user_id', $user->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 11: Recording tetap bekerja (regression)
// ─────────────────────────────────────────────────────────────
test('existing recording logic tetap berfungsi bersama retention', function () {
    $user = User::factory()->create();

    $mock = Mockery::mock(WeatherService::class);
    $mock->shouldReceive('getWeatherWithForecast')
         ->with('Yogyakarta')
         ->once()
         ->andReturn(new \App\DTOs\WeatherData(
             city: 'Yogyakarta', region: 'DI Yogyakarta', country: 'Indonesia',
             localTime: '2026-01-01 10:00', lat: -7.8, lon: 110.4, timezone: 'Asia/Jakarta',
             tempC: 26.0, feelsLikeC: 28.0, conditionText: 'Clear',
             conditionIcon: 'https://cdn.example.com/icon.png', conditionCode: 1000,
             humidity: 70, windKph: 8.0, windDir: 'S', windDegree: 180,
             pressureMb: 1011.0, visibilityKm: 10.0, uvIndex: 5.0, precipMm: 0.0, cloud: 5,
             lastUpdated: '2026-01-01 09:45', isDay: true,
             sunrise: '05:48 AM', sunset: '05:55 PM', moonPhase: 'Full Moon',
             moonrise: '06:30 PM', moonset: '06:30 AM',
             aqiUs: 40.0, pm25: 8.0, pm10: 12.0, co: 150.0, o3: 70.0, no2: 4.0,
             forecast: collect(), hourly: collect(), chanceOfRain: 5,
         ));
    $mock->shouldReceive('getRawForecastData')->never();
    $this->app->instance(WeatherService::class, $mock);

    $response = $this->actingAs($user)->get(route('dashboard', ['city' => 'Yogyakarta']));

    $response->assertStatus(200);
    expect(SearchHistory::where('user_id', $user->id)->where('city', 'Yogyakarta')->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 12: Retrieval tetap bekerja setelah pruning (regression)
// ─────────────────────────────────────────────────────────────
test('retrieval tetap mengembalikan data yang benar setelah pruning', function () {
    $user = User::factory()->create();
    seedHistory($user, 25);

    SearchHistory::pruneForUser($user->id);

    // Retrieval harus mengembalikan tepat 20
    $histories = SearchHistory::forUser($user->id)->get();
    expect($histories)->toHaveCount(20);

    // Urutan terbaru di atas
    expect($histories->first()->searched_at->gt($histories->last()->searched_at))->toBeTrue();
});

// ─────────────────────────────────────────────────────────────
// BATAS KONFIGURASI — pastikan limit dari config digunakan
// ─────────────────────────────────────────────────────────────
test('pruneForUser menggunakan nilai dari config app.search_history_limit', function () {
    // Override config untuk test ini
    Config::set('app.search_history_limit', 5);

    $user = User::factory()->create();
    seedHistory($user, 10); // lebih dari limit 5

    SearchHistory::pruneForUser($user->id);

    // Harus tersisa sesuai limit config yang kita set
    expect(SearchHistory::where('user_id', $user->id)->count())->toBe(5);
});
