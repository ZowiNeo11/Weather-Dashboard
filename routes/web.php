<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavoriteCityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ── PUBLIC ROUTES ──────────────────────────────────────────────────────
// Dashboard cuaca dapat diakses siapa saja (guest maupun authenticated).
// Fitur personal (favorit, riwayat) dikontrol di controller masing-masing
// menggunakan Auth::check() — ditambahkan di Phase C.

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/search', [DashboardController::class, 'search'])->name('weather.search');

// ── GUEST ONLY (redirect ke dashboard jika sudah login) ────────────────
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('auth.register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login.submit');
});

// ── AUTHENTICATED ONLY ─────────────────────────────────────────────────
// Route-route di sini hanya dapat diakses user yang sudah login.
// Guest akan di-redirect ke /login secara otomatis oleh middleware 'auth'.

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

    // ── Favorite Cities ──────────────────────────────────────────────
    // POST   /favorites          → simpan kota ke favorit
    // DELETE /favorites/{id}     → hapus kota dari favorit
    //
    // Konvensi HTTP: POST untuk create, DELETE untuk destroy.
    // CSRF protection aktif via middleware web (default Laravel).
    Route::post('/favorites', [FavoriteCityController::class, 'store'])
        ->name('favorites.store');

    Route::delete('/favorites/{favorite}', [FavoriteCityController::class, 'destroy'])
        ->name('favorites.destroy');
});
