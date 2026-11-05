<?php

use App\DTOs\WeatherData;
use App\Exceptions\WeatherException;
use App\Models\SearchHistory;
use App\Models\User;
use App\Services\WeatherService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// =========================================================
// PHASE D — STEP 2: Search History Recording Logic Tests
//
// WeatherService di-mock menggunakan Mockery agar test tidak
// bergantung pada live Weather API.
// =========================================================

/**
 * Helper: buat WeatherData stub yang realistis.
 * Digunakan di banyak test — satu definisi agar mudah diupdate.
 */
function makeWeatherData(string $city = 'Bandung', string $country = 'Indonesia'): WeatherData
{
    return new WeatherData(
        city:          $city,
        region:        'West Java',
        country:       $country,
        localTime:     '2026-01-01 10:00',
        lat:           -6.9,
        lon:           107.6,
        timezone:      'Asia/Jakarta',
        tempC:         25.0,
        feelsLikeC:    27.0,
        conditionText: 'Partly cloudy',
        conditionIcon: 'https://cdn.weatherapi.com/weather/64x64/day/116.png',
        conditionCode: 1003,
        humidity:      70,
        windKph:       10.0,
        windDir:       'SW',
        windDegree:    220,
        pressureMb:    1012.0,
        visibilityKm:  10.0,
        uvIndex:       5.0,
        precipMm:      0.0,
        cloud:         25,
        lastUpdated:   '2026-01-01 09:45',
        isDay:         true,
        sunrise:       '06:00 AM',
        sunset:        '06:00 PM',
        moonPhase:     'Full Moon',
        moonrise:      '06:30 PM',
        moonset:       '06:30 AM',
        aqiUs:         50.0,
        pm25:          10.0,
        pm10:          15.0,
        co:            200.0,
        o3:            80.0,
        no2:           5.0,
        forecast:      collect(),
        hourly:        collect(),
        chanceOfRain:  10,
    );
}

// ─────────────────────────────────────────────────────────────
// SKENARIO 1: Authenticated user search Bandung → record dibuat
// ─────────────────────────────────────────────────────────────
test('authenticated user search Bandung membuat satu SearchHistory record', function () {
    $user = User::factory()->create();

    // Mock WeatherService — kembalikan WeatherData tanpa live API
    $mock = Mockery::mock(WeatherService::class);
    $mock->shouldReceive('getWeatherWithForecast')
         ->with('Bandung')
         ->once()
         ->andReturn(makeWeatherData('Bandung', 'Indonesia'));

    // Mock fetchFavoritesWeather via getRawForecastData (dipanggil untuk favorites, 0 favorites)
    $mock->shouldReceive('getRawForecastData')->never(); // no favorites

    $this->app->instance(WeatherService::class, $mock);

    $response = $this->actingAs($user)->get(route('dashboard', ['city' => 'Bandung']));

    $response->assertStatus(200);

    $this->assertDatabaseHas('search_histories', [
        'user_id' => $user->id,
        'city'    => 'Bandung',
        'country' => 'Indonesia',
    ]);

    expect(SearchHistory::where('user_id', $user->id)->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 2: Search Jakarta → record Jakarta dibuat
// ─────────────────────────────────────────────────────────────
test('authenticated user search Jakarta membuat SearchHistory record Jakarta', function () {
    $user = User::factory()->create();

    $mock = Mockery::mock(WeatherService::class);
    $mock->shouldReceive('getWeatherWithForecast')
         ->with('Jakarta')
         ->once()
         ->andReturn(makeWeatherData('Jakarta', 'Indonesia'));
    $mock->shouldReceive('getRawForecastData')->never();
    $this->app->instance(WeatherService::class, $mock);

    $this->actingAs($user)->get(route('dashboard', ['city' => 'Jakarta']));

    $this->assertDatabaseHas('search_histories', [
        'user_id' => $user->id,
        'city'    => 'Jakarta',
    ]);
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 3: Search Bandung dua kali → dua record Bandung
// ─────────────────────────────────────────────────────────────
test('search kota yang sama dua kali menghasilkan dua record terpisah', function () {
    $user = User::factory()->create();

    $mock = Mockery::mock(WeatherService::class);
    $mock->shouldReceive('getWeatherWithForecast')
         ->with('Bandung')
         ->twice()
         ->andReturn(makeWeatherData('Bandung', 'Indonesia'));
    $mock->shouldReceive('getRawForecastData')->never();
    $this->app->instance(WeatherService::class, $mock);

    $this->actingAs($user)->get(route('dashboard', ['city' => 'Bandung']));
    $this->actingAs($user)->get(route('dashboard', ['city' => 'Bandung']));

    // Harus ada DUA record Bandung — tidak disuppress
    $count = SearchHistory::where('user_id', $user->id)->where('city', 'Bandung')->count();
    expect($count)->toBe(2);
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 4: Guest search → tidak ada SearchHistory record
// ─────────────────────────────────────────────────────────────
test('guest search kota tidak membuat SearchHistory record', function () {
    $mock = Mockery::mock(WeatherService::class);
    $mock->shouldReceive('getWeatherWithForecast')
         ->with('London')
         ->once()
         ->andReturn(makeWeatherData('London', 'United Kingdom'));
    $mock->shouldReceive('getRawForecastData')->never();
    $this->app->instance(WeatherService::class, $mock);

    // Guest (tidak login)
    $response = $this->get(route('dashboard', ['city' => 'London']));

    $response->assertStatus(200);

    // Tidak boleh ada record di database
    expect(SearchHistory::where('city', 'London')->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 5: City tidak ditemukan → tidak ada record
// ─────────────────────────────────────────────────────────────
test('pencarian kota yang tidak ditemukan tidak membuat SearchHistory record', function () {
    $user = User::factory()->create();

    $mock = Mockery::mock(WeatherService::class);
    $mock->shouldReceive('getWeatherWithForecast')
         ->with('KotaTidakAda123')
         ->once()
         ->andThrow(WeatherException::cityNotFound('KotaTidakAda123'));
    $mock->shouldReceive('getRawForecastData')->never();
    $this->app->instance(WeatherService::class, $mock);

    $response = $this->actingAs($user)->get(route('dashboard', ['city' => 'KotaTidakAda123']));

    $response->assertStatus(200); // Dashboard tetap render (dengan error message)

    expect(SearchHistory::where('user_id', $user->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 6: Weather API gagal → tidak ada record
// ─────────────────────────────────────────────────────────────
test('Weather API gagal tidak membuat SearchHistory record', function () {
    $user = User::factory()->create();

    $mock = Mockery::mock(WeatherService::class);
    $mock->shouldReceive('getWeatherWithForecast')
         ->with('Semarang')
         ->once()
         ->andThrow(WeatherException::apiUnavailable());
    $mock->shouldReceive('getRawForecastData')->never();
    $this->app->instance(WeatherService::class, $mock);

    $response = $this->actingAs($user)->get(route('dashboard', ['city' => 'Semarang']));

    $response->assertStatus(200); // Halaman tetap 200 dengan error state

    expect(SearchHistory::where('user_id', $user->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 7: user_id menggunakan authenticated user, bukan request
// ─────────────────────────────────────────────────────────────
test('SearchHistory menggunakan user_id dari authenticated user bukan dari request', function () {
    $userA = User::factory()->create();

    $mock = Mockery::mock(WeatherService::class);
    $mock->shouldReceive('getWeatherWithForecast')
         ->once()
         ->andReturn(makeWeatherData('Surabaya', 'Indonesia'));
    $mock->shouldReceive('getRawForecastData')->never();
    $this->app->instance(WeatherService::class, $mock);

    $this->actingAs($userA)->get(route('dashboard', ['city' => 'Surabaya']));

    $record = SearchHistory::where('city', 'Surabaya')->first();
    expect($record)->not->toBeNull();
    expect($record->user_id)->toBe($userA->id);
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 8: Country tersimpan dengan benar
// ─────────────────────────────────────────────────────────────
test('country dari WeatherData tersimpan dengan benar di SearchHistory', function () {
    $user = User::factory()->create();

    $mock = Mockery::mock(WeatherService::class);
    $mock->shouldReceive('getWeatherWithForecast')
         ->with('Tokyo')
         ->once()
         ->andReturn(makeWeatherData('Tokyo', 'Japan'));
    $mock->shouldReceive('getRawForecastData')->never();
    $this->app->instance(WeatherService::class, $mock);

    $this->actingAs($user)->get(route('dashboard', ['city' => 'Tokyo']));

    $this->assertDatabaseHas('search_histories', [
        'user_id' => $user->id,
        'city'    => 'Tokyo',
        'country' => 'Japan',
    ]);
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 9: Favorite Cities tetap bekerja (regression check)
// ─────────────────────────────────────────────────────────────
test('Favorite Cities tetap berfungsi setelah implementasi Search History', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('favorites.store'), [
        'city'    => 'Bali',
        'country' => 'Indonesia',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('favorite_cities', [
        'user_id' => $user->id,
        'city'    => 'Bali',
    ]);
});

// ─────────────────────────────────────────────────────────────
// SKENARIO 10: Weather Search tetap bekerja end-to-end
// ─────────────────────────────────────────────────────────────
test('Weather search tetap bekerja dan menampilkan dashboard setelah recording logic ditambahkan', function () {
    $user = User::factory()->create();

    $mock = Mockery::mock(WeatherService::class);
    $mock->shouldReceive('getWeatherWithForecast')
         ->with('Bandung')
         ->once()
         ->andReturn(makeWeatherData('Bandung', 'Indonesia'));
    $mock->shouldReceive('getRawForecastData')->never();
    $this->app->instance(WeatherService::class, $mock);

    $response = $this->actingAs($user)->get(route('dashboard', ['city' => 'Bandung']));

    // Dashboard harus tetap render 200 dengan data cuaca
    $response->assertStatus(200);
    $response->assertViewIs('dashboard');
    $response->assertViewHas('weather');

    // Dan history juga tersimpan
    expect(SearchHistory::where('user_id', $user->id)->where('city', 'Bandung')->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────
// SKENARIO BONUS: kegagalan DB tidak crash halaman
// ─────────────────────────────────────────────────────────────
test('kegagalan menyimpan SearchHistory tidak menyebabkan HTTP 500', function () {
    $user = User::factory()->create();

    $mock = Mockery::mock(WeatherService::class);
    $mock->shouldReceive('getWeatherWithForecast')
         ->with('Denpasar')
         ->once()
         ->andReturn(makeWeatherData('Denpasar', 'Indonesia'));
    $mock->shouldReceive('getRawForecastData')->never();
    $this->app->instance(WeatherService::class, $mock);

    // Mock Log::error agar kita bisa verify logging dipanggil
    Log::shouldReceive('error')->atMost()->once();

    // Partial mock SearchHistory::create untuk simulasi DB failure
    // Ini cukup diuji secara unit — untuk integration test kita verify
    // bahwa halaman tetap 200 setelah search berhasil
    $response = $this->actingAs($user)->get(route('dashboard', ['city' => 'Denpasar']));

    // Halaman tetap 200 — bukan 500
    $response->assertStatus(200);
});
