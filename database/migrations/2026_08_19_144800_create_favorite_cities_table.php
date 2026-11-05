<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: favorite_cities
 *
 * Menyimpan kota favorit milik setiap user.
 * Unique constraint (user_id, city) memastikan satu user
 * tidak bisa menyimpan kota yang sama lebih dari satu kali,
 * namun user berbeda tetap boleh menyimpan kota yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorite_cities', function (Blueprint $table) {
            $table->id();

            // Foreign key ke users.id — cascade delete agar data
            // favorite terhapus otomatis saat user dihapus
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // Nama kota, maksimal 100 karakter sesuai validasi controller
            $table->string('city', 100);

            // Kode/nama negara (contoh: "Indonesia", "ID")
            // Nullable karena bisa saja tidak tersedia dari API
            $table->string('country', 100)->nullable();

            $table->timestamps();

            // Unique per user: User A → Bandung hanya boleh satu kali.
            // User B → Bandung tetap diperbolehkan karena user_id berbeda.
            $table->unique(['user_id', 'city']);

            // Index pada city untuk mempercepat query pencarian kota
            $table->index('city');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_cities');
    }
};
