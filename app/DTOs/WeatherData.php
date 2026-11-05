<?php

namespace App\DTOs;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * WeatherData DTO
 *
 * Immutable data object yang merepresentasikan seluruh data cuaca
 * yang dibutuhkan UI: current, forecast 7 hari, hourly 24 jam, dan AQI.
 */
readonly class WeatherData
{
    public function __construct(
        // --- Lokasi ---
        public string $city,
        public string $region,
        public string $country,
        public string $localTime,
        public float  $lat,
        public float  $lon,
        public string $timezone,

        // --- Suhu ---
        public float $tempC,
        public float $feelsLikeC,

        // --- Kondisi ---
        public string $conditionText,
        public string $conditionIcon,
        public int    $conditionCode,

        // --- Detail atmosfer ---
        public int    $humidity,
        public float  $windKph,
        public string $windDir,
        public int    $windDegree,
        public float  $pressureMb,
        public float  $visibilityKm,
        public float  $uvIndex,
        public float  $precipMm,
        public int    $cloud,

        // --- Metadata ---
        public string $lastUpdated,
        public bool   $isDay,

        // --- Astro (hari ini) ---
        public string $sunrise,
        public string $sunset,
        public string $moonPhase,
        public string $moonrise,
        public string $moonset,

        // --- AQI ---
        public ?float  $aqiUs,       // US EPA Index (1-6)
        public ?float  $pm25,
        public ?float  $pm10,
        public ?float  $co,
        public ?float  $o3,
        public ?float  $no2,

        // --- Forecast 7 hari (Collection of array) ---
        /** @var Collection<int, array<string, mixed>> */
        public Collection $forecast,

        // --- Hourly forecast hari ini (Collection of array) ---
        /** @var Collection<int, array<string, mixed>> */
        public Collection $hourly,

        // --- Chance of rain hari ini (max dari hourly) ---
        public int $chanceOfRain,
    ) {}

    /**
     * Factory method dari response /forecast.json WeatherAPI.com
     *
     * @param array<string, mixed> $data
     */
    public static function fromForecastResponse(array $data): static
    {
        $location     = $data['location'];
        $current      = $data['current'];
        $forecastDays = $data['forecast']['forecastday'] ?? [];
        $todayAstro   = $forecastDays[0]['astro'] ?? [];
        $todayHourly  = $forecastDays[0]['hour'] ?? [];
        $aqi          = $current['air_quality'] ?? null;

        // --- Normalize forecast 7 hari ---
        $forecast = collect($forecastDays)->map(function (array $day, int $index) {
            $date = Carbon::parse($day['date']);
            return [
                'date'          => $day['date'],
                'day_name'      => $index === 0 ? 'Hari Ini' : $date->translatedFormat('D'),
                'date_short'    => $date->translatedFormat('d M'),
                'icon'          => 'https:' . $day['day']['condition']['icon'],
                'condition'     => $day['day']['condition']['text'],
                'code'          => $day['day']['condition']['code'],
                'max_c'         => round($day['day']['maxtemp_c'], 1),
                'min_c'         => round($day['day']['mintemp_c'], 1),
                'chance_rain'   => $day['day']['daily_chance_of_rain'] ?? 0,
                'total_precip'  => $day['day']['totalprecip_mm'] ?? 0,
                'uv'            => $day['day']['uv'] ?? 0,
                'active'        => $index === 0,
            ];
        });

        // --- Normalize hourly 24 jam ---
        $hourly = collect($todayHourly)->map(function (array $hour) {
            $time = Carbon::parse($hour['time']);
            return [
                'time'         => $time->format('H:i'),
                'hour'         => (int) $time->format('H'),
                'icon'         => 'https:' . $hour['condition']['icon'],
                'condition'    => $hour['condition']['text'],
                'temp_c'       => round($hour['temp_c'], 1),
                'feels_like_c' => round($hour['feelslike_c'], 1),
                'chance_rain'  => $hour['chance_of_rain'] ?? 0,
                'wind_kph'     => round($hour['wind_kph'], 1),
                'humidity'     => $hour['humidity'],
                'is_day'       => (bool) $hour['is_day'],
            ];
        });

        // Chance of rain hari ini = nilai tertinggi dari hourly
        $chanceOfRain = $hourly->max('chance_rain') ?? 0;

        return new static(
            city:         $location['name'],
            region:       $location['region'],
            country:      $location['country'],
            localTime:    $location['localtime'],
            lat:          (float) $location['lat'],
            lon:          (float) $location['lon'],
            timezone:     $location['tz_id'],

            tempC:        (float) $current['temp_c'],
            feelsLikeC:   (float) $current['feelslike_c'],

            conditionText: $current['condition']['text'],
            conditionIcon: 'https:' . $current['condition']['icon'],
            conditionCode: (int) $current['condition']['code'],

            humidity:     (int) $current['humidity'],
            windKph:      (float) $current['wind_kph'],
            windDir:      $current['wind_dir'],
            windDegree:   (int) $current['wind_degree'],
            pressureMb:   (float) $current['pressure_mb'],
            visibilityKm: (float) $current['vis_km'],
            uvIndex:      (float) $current['uv'],
            precipMm:     (float) ($current['precip_mm'] ?? 0),
            cloud:        (int) ($current['cloud'] ?? 0),

            lastUpdated:  $current['last_updated'],
            isDay:        (bool) $current['is_day'],

            sunrise:   $todayAstro['sunrise']    ?? '--:--',
            sunset:    $todayAstro['sunset']     ?? '--:--',
            moonPhase: $todayAstro['moon_phase'] ?? 'Unknown',
            moonrise:  $todayAstro['moonrise']   ?? '--:--',
            moonset:   $todayAstro['moonset']    ?? '--:--',

            aqiUs: $aqi ? (float) ($aqi['us-epa-index'] ?? 0) : null,
            pm25:  $aqi ? (float) ($aqi['pm2_5'] ?? 0) : null,
            pm10:  $aqi ? (float) ($aqi['pm10'] ?? 0) : null,
            co:    $aqi ? (float) ($aqi['co'] ?? 0) : null,
            o3:    $aqi ? (float) ($aqi['o3'] ?? 0) : null,
            no2:   $aqi ? (float) ($aqi['no2'] ?? 0) : null,

            forecast:     $forecast,
            hourly:       $hourly,
            chanceOfRain: (int) $chanceOfRain,
        );
    }

    // =========================================================
    // Helper Methods — logika presentasi tetap di DTO, bukan View
    // =========================================================

    /** Label & warna UV Index */
    public function uvLabel(): string
    {
        return match(true) {
            $this->uvIndex <= 2  => 'Rendah',
            $this->uvIndex <= 5  => 'Sedang',
            $this->uvIndex <= 7  => 'Tinggi',
            $this->uvIndex <= 10 => 'Sangat Tinggi',
            default              => 'Ekstrem',
        };
    }

    public function uvColorClass(): string
    {
        return match(true) {
            $this->uvIndex <= 2  => 'bg-green-500/20 text-green-300',
            $this->uvIndex <= 5  => 'bg-yellow-500/20 text-yellow-300',
            $this->uvIndex <= 7  => 'bg-orange-500/20 text-orange-300',
            $this->uvIndex <= 10 => 'bg-red-500/20 text-red-300',
            default              => 'bg-purple-500/20 text-purple-300',
        };
    }

    /** Label & warna AQI (US EPA Scale 1-6) */
    public function aqiLabel(): string
    {
        if ($this->aqiUs === null) return 'N/A';
        return match((int) $this->aqiUs) {
            1       => 'Baik',
            2       => 'Sedang',
            3       => 'Tidak Sehat*',
            4       => 'Tidak Sehat',
            5       => 'Sangat Tidak Sehat',
            default => 'Berbahaya',
        };
    }

    public function aqiColorClass(): string
    {
        if ($this->aqiUs === null) return 'bg-slate-500/20 text-slate-300';
        return match((int) $this->aqiUs) {
            1       => 'bg-green-500/20 text-green-300',
            2       => 'bg-yellow-500/20 text-yellow-300',
            3       => 'bg-orange-500/20 text-orange-300',
            4       => 'bg-red-500/20 text-red-300',
            5       => 'bg-purple-500/20 text-purple-300',
            default => 'bg-rose-900/40 text-rose-300',
        };
    }

    /** Kelas CSS untuk dynamic background berdasarkan kondisi cuaca */
    public function bgThemeClass(): string
    {
        if (! $this->isDay) {
            return 'theme-night';
        }

        // Mapping condition code WeatherAPI → tema
        // Ref: https://www.weatherapi.com/docs/weather_conditions.json
        $code = $this->conditionCode;

        // Hujan
        if (in_array($code, [1063,1150,1153,1168,1171,1180,1183,1186,1189,1192,1195,1198,1201,1240,1243,1246])) {
            return 'theme-rain';
        }

        // Badai / petir
        if (in_array($code, [1087,1273,1276,1279,1282])) {
            return 'theme-storm';
        }

        // Salju
        if (in_array($code, [1066,1069,1072,1114,1117,1204,1207,1210,1213,1216,1219,1222,1225,1237,1249,1252,1255,1258,1261,1264])) {
            return 'theme-snow';
        }

        // Mendung / berawan tebal
        if (in_array($code, [1006,1009,1030,1135,1147])) {
            return 'theme-cloudy';
        }

        // Sebagian berawan
        if (in_array($code, [1003])) {
            return 'theme-partly-cloudy';
        }

        // Default: cerah
        return 'theme-sunny';
    }

    /** Fase bulan dalam Bahasa Indonesia */
    public function moonPhaseId(): string
    {
        return match($this->moonPhase) {
            'New Moon'        => 'Bulan Baru',
            'Waxing Crescent' => 'Sabit Muda',
            'First Quarter'   => 'Kuartal Pertama',
            'Waxing Gibbous'  => 'Cembung Muda',
            'Full Moon'       => 'Bulan Purnama',
            'Waning Gibbous'  => 'Cembung Tua',
            'Last Quarter'    => 'Kuartal Terakhir',
            'Waning Crescent' => 'Sabit Tua',
            default           => $this->moonPhase,
        };
    }

    /** Emoji ikon fase bulan */
    public function moonPhaseIcon(): string
    {
        return match($this->moonPhase) {
            'New Moon'        => '🌑',
            'Waxing Crescent' => '🌒',
            'First Quarter'   => '🌓',
            'Waxing Gibbous'  => '🌔',
            'Full Moon'       => '🌕',
            'Waning Gibbous'  => '🌖',
            'Last Quarter'    => '🌗',
            'Waning Crescent' => '🌘',
            default           => '🌙',
        };
    }

    /** Format waktu lokal */
    public function formattedLocalTime(): string
    {
        try {
            return Carbon::parse($this->localTime)->translatedFormat('d M Y, H:i');
        } catch (\Throwable) {
            return $this->localTime;
        }
    }
}
