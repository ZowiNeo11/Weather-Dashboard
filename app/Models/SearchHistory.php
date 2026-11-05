<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SearchHistory Model
 *
 * Merekam setiap pencarian kota yang dilakukan oleh user.
 * Satu user boleh memiliki banyak record untuk kota yang sama
 * (setiap pencarian = satu record baru).
 *
 * Tabel  : search_histories
 * FK     : user_id → users.id (cascade delete)
 * Index  : (user_id, searched_at) untuk query riwayat terbaru
 *
 * @property int              $id
 * @property int              $user_id
 * @property string           $city
 * @property string|null      $country
 * @property \Carbon\Carbon   $searched_at
 * @property \Carbon\Carbon   $created_at
 * @property \Carbon\Carbon   $updated_at
 *
 * @property-read User $user
 */
class SearchHistory extends Model
{
    use HasFactory;

    /**
     * Kolom yang boleh diisi via mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'city',
        'country',
        'searched_at',
    ];

    /**
     * Cast tipe data kolom.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'searched_at' => 'datetime',
            'created_at'  => 'datetime',
            'updated_at'  => 'datetime',
        ];
    }

    // =========================================================
    // RELATIONSHIPS
    // =========================================================

    /**
     * SearchHistory dimiliki oleh satu User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =========================================================
    // SCOPES
    // =========================================================

    /**
     * Scope: riwayat milik user tertentu, urut dari terbaru.
     *
     * Ordering:
     *   1. searched_at DESC — pencarian terbaru di atas
     *   2. id DESC           — tiebreaker jika searched_at sama (cegah non-deterministic order)
     *
     * Select hanya kolom yang diperlukan consumer:
     *   id, user_id, city, country, searched_at, created_at
     * user_id disertakan agar relationship lazy-loading tetap bekerja.
     *
     * Contoh pemakaian:
     *   SearchHistory::forUser($userId)->get();
     *   SearchHistory::forUser($userId)->limit(10)->get();
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query
            ->select(['id', 'user_id', 'city', 'country', 'searched_at', 'created_at'])
            ->where('user_id', $userId)
            ->orderByDesc('searched_at')
            ->orderByDesc('id'); // tiebreaker — deterministic order saat searched_at sama
    }

    /**
     * Scope: ambil pencarian unik (distinct city) per user,
     * berguna untuk tampilan "kota yang pernah dicari".
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDistinctCities($query)
    {
        return $query->select('user_id', 'city', 'country')
                     ->distinct();
    }

    // =========================================================
    // RETENTION
    // =========================================================

    /**
     * Terapkan retention policy untuk user tertentu.
     *
     * Setelah record baru disimpan, hapus record-record lama yang melebihi
     * batas maksimum `config('app.search_history_limit')` per user.
     *
     * Strategi:
     *   1. Ambil ID dari N record terbaru milik user ini.
     *   2. Hapus semua record milik user ini yang ID-nya TIDAK termasuk dalam N tersebut.
     *
     * Deletion dilakukan di database layer (tidak load record ke PHP memory)
     * sehingga efisien dan memanfaatkan index (user_id, searched_at) yang sudah ada.
     *
     * Isolation:
     *   `whereNot` dan `where('user_id', $userId)` memastikan HANYA record
     *   milik user ini yang terpengaruh — record user lain aman.
     *
     * Error handling:
     *   Exception di-rethrow agar caller (recordSearchHistory) dapat menangkap
     *   dan melakukan logging sesuai error isolation strategy Step 2.
     *
     * @param  int $userId
     */
    public static function pruneForUser(int $userId): void
    {
        $limit = (int) config('app.search_history_limit', 20);

        // Ambil ID dari $limit record terbaru milik user ini.
        // Ordering: searched_at DESC, id DESC (konsisten dengan scopeForUser).
        $keepIds = static::where('user_id', $userId)
            ->orderByDesc('searched_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('id');

        if ($keepIds->isEmpty()) {
            return; // Tidak ada record sama sekali — tidak ada yang perlu dihapus
        }

        // Hapus record milik user ini yang tidak masuk dalam daftar keep.
        // whereNotIn + where(user_id) = deletion yang aman dan user-scoped.
        static::where('user_id', $userId)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}
