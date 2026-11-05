<?php

namespace App\Services;

use App\DTOs\WeatherData;
use App\Exceptions\WeatherException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WeatherService
 *
 * Single Responsibility: satu-satunya kelas yang boleh berkomunikasi
 * dengan WeatherAPI.com. Controller tidak boleh tahu detail HTTP, URL,
 * atau struktur response API — semua dienkapsulasi di sini.
 */
class WeatherService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey  = config('services.weatherapi.key', '');
        $this->baseUrl = config('services.weatherapi.base_url', 'https://api.weatherapi.com/v1');
    }

    /**
     * Ambil data cuaca lengkap: current + forecast 7 hari + hourly + AQI.
     * Satu request ke endpoint /forecast.json sudah mencakup semua data yang dibutuhkan.
     *
     * @throws WeatherException
     */
    public function getWeatherWithForecast(string $city): WeatherData
    {
        $response = $this->makeRequest('/forecast.json', $this->forecastParams($city));

        return WeatherData::fromForecastResponse($response->json());
    }

    /**
     * Ambil raw array response dari WeatherAPI tanpa reconstruct ke DTO.
     *
     * Digunakan oleh DashboardController::fetchFavoritesWeather() untuk menyimpan
     * ke cache sebagai array — lebih aman daripada menyimpan PHP object langsung,
     * karena array tidak bergantung pada class definition WeatherData.
     *
     * @throws WeatherException
     * @return array<string, mixed>
     */
    public function getRawForecastData(string $city): array
    {
        $response = $this->makeRequest('/forecast.json', $this->forecastParams($city));

        return $response->json();
    }

    /**
     * Parameter standar untuk request /forecast.json.
     * Diextract ke method sendiri agar getWeatherWithForecast() dan
     * getRawForecastData() tidak menduplikasi definisi yang sama.
     *
     * @return array<string, string|int>
     */
    private function forecastParams(string $city): array
    {
        return [
            'q'      => $city,
            'days'   => 7,
            'aqi'    => 'yes',
            'alerts' => 'no',
        ];
    }

    /**
     * Kirim HTTP request ke WeatherAPI dengan error handling terpusat.
     *
     * @param  array<string, string|int> $params
     * @throws WeatherException
     */
    private function makeRequest(string $endpoint, array $params = []): Response
    {
        if (empty($this->apiKey)) {
            Log::error('WeatherAPI: API key tidak dikonfigurasi di .env');
            throw WeatherException::apiUnavailable();
        }

        try {
            $response = Http::timeout(15)
                ->retry(
                    times: 2,
                    sleepMilliseconds: 500,
                    // Hanya retry untuk network error — JANGAN retry 4xx
                    // (Laravel 13: parameter ke-2 callback adalah PendingRequest, bukan Response)
                    when: fn (\Throwable $e): bool => $e instanceof ConnectionException,
                    throw: false,
                )
                ->acceptJson()
                ->get($this->baseUrl . $endpoint, array_merge(
                    ['key' => $this->apiKey],
                    $params,
                ));

        } catch (ConnectionException $e) {
            Log::error('WeatherAPI: Koneksi gagal', [
                'message' => $e->getMessage(),
                'city'    => $params['q'] ?? 'unknown',
            ]);
            throw WeatherException::apiUnavailable();
        } catch (RequestException $e) {
            Log::error('WeatherAPI: RequestException', [
                'status' => $e->response?->status(),
                'city'   => $params['q'] ?? 'unknown',
            ]);
            if ($e->response && in_array($e->response->status(), [400, 404])) {
                throw WeatherException::cityNotFound($params['q'] ?? 'unknown');
            }
            throw WeatherException::apiUnavailable();
        }

        // Handle HTTP error codes
        if (in_array($response->status(), [400, 404])) {
            Log::warning('WeatherAPI: Kota tidak ditemukan', [
                'city'       => $params['q'] ?? 'unknown',
                'error_code' => $response->json('error.code'),
            ]);
            throw WeatherException::cityNotFound($params['q'] ?? 'unknown');
        }

        if ($response->serverError()) {
            Log::error('WeatherAPI: Server error', ['status' => $response->status()]);
            throw WeatherException::apiUnavailable();
        }

        if ($response->failed()) {
            Log::error('WeatherAPI: Request gagal', ['status' => $response->status()]);
            throw WeatherException::invalidResponse();
        }

        if (! $response->json('current') || ! $response->json('location')) {
            Log::error('WeatherAPI: Struktur response tidak valid');
            throw WeatherException::invalidResponse();
        }

        return $response;
    }
}
