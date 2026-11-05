<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: search_histories
 *
 * Menyimpan riwayat pencarian kota oleh setiap user.
 * Tidak ada unique constraint di sini — user boleh mencari
 * kota yang sama berkali-kali (setiap pencarian = satu record baru).
 *
 * Catatan desain:
 * - Hanya menyimpan data minimal yang dibutuhkan (city, country, searched_at).
 * - TIDAK menyimpan seluruh JSON response API cuaca untuk menghindari
 *   pemborosan storage dan menjaga tabel tetap lean.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_histories', function (Blueprint $table) {
            $table->id();

            // Foreign key ke users.id — cascade delete agar riwayat
            // pencarian terhapus otomatis saat user dihapus
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // Nama kota yang dicari
            $table->string('city', 100);

            // Nama negara dari hasil API (nullable karena pencarian gagal
            // pun tetap bisa direkam sebagai riwayat)
            $table->string('country', 100)->nullable();

            // Waktu eksak pencarian dilakukan — berbeda dari created_at
            // agar semantiknya jelas dan bisa diquery secara independen
            $table->timestamp('searched_at')->useCurrent();

            $table->timestamps();

            // Index pada (user_id, searched_at) untuk mempercepat query
            // "tampilkan riwayat terbaru milik user X"
            $table->index(['user_id', 'searched_at']);

            // Index pada city untuk query "siapa saja yang pernah search kota ini"
            $table->index('city');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_histories');
    }
};
