<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // =========================================================
    // RELATIONSHIPS
    // =========================================================

    /**
     * User memiliki banyak kota favorit.
     * Urut dari yang paling baru ditambahkan.
     *
     * Contoh pemakaian:
     *   $user->favoriteCities           → semua favorit
     *   $user->favoriteCities()->where('city', 'Bandung')->exists()
     */
    public function favoriteCities(): HasMany
    {
        return $this->hasMany(FavoriteCity::class)->latest();
    }

    /**
     * User memiliki banyak riwayat pencarian.
     * Urut dari searched_at terbaru.
     *
     * Contoh pemakaian:
     *   $user->searchHistories           → semua riwayat
     *   $user->searchHistories()->limit(5)->get()
     */
    public function searchHistories(): HasMany
    {
        return $this->hasMany(SearchHistory::class)
                    ->orderByDesc('searched_at');
    }
}
