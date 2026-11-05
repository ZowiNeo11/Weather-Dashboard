<?php

namespace App\Http\Controllers;

use App\Models\FavoriteCity;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * FavoriteCityController
 *
 * Mengelola operasi add/remove Favorite Cities.
 *
 * Prinsip keamanan:
 *   - user_id SELALU diambil dari Auth::id(), tidak pernah dari request
 *   - Setiap operasi di-scope ke authenticated user → mencegah IDOR
 *   - destroy() menggunakan whereUserId sebelum find → ownership enforced di DB layer
 */
class FavoriteCityController extends Controller
{
    /**
     * Tambahkan kota ke daftar favorit authenticated user.
     *
     * Duplicate handling berlapis:
     *   1. Application layer: cek dengan exists() sebelum insert
     *   2. Database layer: unique constraint (user_id, city) sebagai safety net
     *
     * POST /favorites
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // city: wajib diisi, harus string, minimal 2 karakter (nama kota realistis),
            // maksimal 100 karakter (konsisten dengan kolom DB dan search validation).
            // 'string' rule memastikan array/object injection ditolak sebelum diproses.
            'city'    => ['required', 'string', 'min:2', 'max:100'],

            // country: opsional — berasal dari Weather API response.
            // Nullable karena kota bisa saja tidak memiliki country dari API.
            'country' => ['nullable', 'string', 'max:100'],
        ], [
            'city.required' => 'Nama kota wajib diisi.',
            'city.string'   => 'Nama kota harus berupa teks.',
            'city.min'      => 'Nama kota minimal 2 karakter.',
            'city.max'      => 'Nama kota terlalu panjang (maksimal 100 karakter).',
        ]);

        $userId  = Auth::id();
        $city    = trim($validated['city']);
        $country = isset($validated['country']) ? trim($validated['country']) : null;

        // Application-layer duplicate check — cegah round-trip ke DB jika sudah ada
        $alreadyFavorited = FavoriteCity::where('user_id', $userId)
            ->where('city', $city)
            ->exists();

        if (! $alreadyFavorited) {
            try {
                FavoriteCity::create([
                    'user_id' => $userId,
                    'city'    => $city,
                    'country' => $country,
                ]);

                return back()->with('favorite_status', [
                    'type'    => 'added',
                    'message' => "{$city} ditambahkan ke favorit.",
                ]);

            } catch (UniqueConstraintViolationException) {
                // Race condition: dua request bersamaan lolos application check
                // DB constraint menangkap ini — bukan error, cukup tampilkan state favorited
            }
        }

        // Sudah favorit (dari application check atau race condition)
        return back()->with('favorite_status', [
            'type'    => 'already',
            'message' => "{$city} sudah ada di daftar favorit.",
        ]);
    }

    /**
     * Hapus kota dari daftar favorit authenticated user.
     *
     * Authorization:
     *   Query di-scope dengan where('user_id', Auth::id()) SEBELUM find —
     *   jika {favorite} bukan milik user ini, query mengembalikan null
     *   dan kita abort 404. Ini mencegah IDOR secara eksplisit.
     *
     * DELETE /favorites/{favorite}
     */
    public function destroy(int $favorite): RedirectResponse
    {
        // Scope ke user saat ini — user lain tidak bisa hapus favorit user ini
        $favoriteCity = FavoriteCity::where('id', $favorite)
            ->where('user_id', Auth::id())
            ->first();

        if (! $favoriteCity) {
            // Bisa berarti: record tidak ada, atau bukan milik user ini
            // Kembalikan 404 tanpa memberi tahu apakah record exist atau tidak
            abort(404);
        }

        $cityName = $favoriteCity->city;
        $favoriteCity->delete();

        return back()->with('favorite_status', [
            'type'    => 'removed',
            'message' => "{$cityName} dihapus dari favorit.",
        ]);
    }
}
