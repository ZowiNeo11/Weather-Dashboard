<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * WeatherException
 *
 * Exception khusus untuk semua error yang berkaitan dengan WeatherService.
 * Memisahkan error domain cuaca dari generic exception Laravel,
 * sehingga controller bisa catch secara spesifik dan terarah.
 */
class WeatherException extends RuntimeException
{
    /**
     * Buat exception untuk kota yang tidak ditemukan.
     */
    public static function cityNotFound(string $city): static
    {
        return new static("Kota \"{$city}\" tidak ditemukan. Periksa kembali nama kota.");
    }

    /**
     * Buat exception saat API tidak dapat dihubungi.
     */
    public static function apiUnavailable(): static
    {
        return new static('Layanan cuaca sedang tidak tersedia. Coba beberapa saat lagi.');
    }

    /**
     * Buat exception untuk respons API yang tidak valid.
     */
    public static function invalidResponse(): static
    {
        return new static('Respons dari server cuaca tidak valid. Coba beberapa saat lagi.');
    }
}
