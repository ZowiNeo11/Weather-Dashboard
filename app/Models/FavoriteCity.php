<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FavoriteCity Model
 *
 * Merepresentasikan satu kota favorit milik seorang user.
 *
 * Tabel  : favorite_cities
 * FK     : user_id → users.id (cascade delete)
 * Unique : (user_id, city) — satu user tidak boleh duplikasi kota
 *
 * @property int         $id
 * @property int         $user_id
 * @property string      $city
 * @property string|null $country
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User $user
 */
class FavoriteCity extends Model
{
    use HasFactory;

    /**
     * Kolom yang boleh diisi via mass assignment.
     * user_id disertakan agar bisa diisi via relationship create()
     * maupun FavoriteCity::create([...]).
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'city',
        'country',
    ];

    /**
     * Cast tipe data kolom.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // =========================================================
    // RELATIONSHIPS
    // =========================================================

    /**
     * FavoriteCity dimiliki oleh satu User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =========================================================
    // SCOPES — query helper yang sering dipakai
    // =========================================================

    /**
     * Scope: filter favorit milik user tertentu, urut terbaru.
     *
     * Contoh pemakaian:
     *   FavoriteCity::forUser($userId)->get();
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId)->latest();
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Buat cache key yang konsisten untuk weather data per kota favorit.
     * Disimpan di model karena ini domain knowledge tentang FavoriteCity,
     * bukan tanggung jawab controller atau service.
     *
     * Format: "weather_fav_{normalized_city}"
     * Contoh: "Kuala Lumpur" → "weather_fav_kuala_lumpur"
     */
    public function weatherCacheKey(): string
    {
        return 'weather_fav_' . strtolower(str_replace(' ', '_', $this->city));
    }
}
