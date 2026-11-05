<?php

use App\Models\FavoriteCity;
use App\Models\SearchHistory;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

// =========================================================
// PHASE D — STEP 1: Database & Model Foundation Tests
// =========================================================

// ── 1. Tabel search_histories dapat dibuat ──────────────────
test('tabel search_histories ada di database', function () {
    expect(Schema::hasTable('search_histories'))->toBeTrue();
});

test('tabel search_histories memiliki kolom yang benar', function () {
    expect(Schema::hasColumn('search_histories', 'id'))->toBeTrue();
    expect(Schema::hasColumn('search_histories', 'user_id'))->toBeTrue();
    expect(Schema::hasColumn('search_histories', 'city'))->toBeTrue();
    expect(Schema::hasColumn('search_histories', 'country'))->toBeTrue();
    expect(Schema::hasColumn('search_histories', 'searched_at'))->toBeTrue();
    expect(Schema::hasColumn('search_histories', 'created_at'))->toBeTrue();
    expect(Schema::hasColumn('search_histories', 'updated_at'))->toBeTrue();
});

// ── 2. SearchHistory dapat dibuat untuk authenticated user ──
test('SearchHistory dapat dibuat untuk user tertentu', function () {
    $user = User::factory()->create();

    $history = SearchHistory::create([
        'user_id'     => $user->id,
        'city'        => 'Bandung',
        'country'     => 'Indonesia',
        'searched_at' => now(),
    ]);

    expect($history->exists)->toBeTrue();
    expect($history->city)->toBe('Bandung');
    expect($history->user_id)->toBe($user->id);

    $this->assertDatabaseHas('search_histories', [
        'user_id' => $user->id,
        'city'    => 'Bandung',
    ]);
});

// ── 3. SearchHistory memiliki relationship ke User ──────────
test('SearchHistory memiliki relationship belongsTo User', function () {
    $user = User::factory()->create();

    $history = SearchHistory::create([
        'user_id'     => $user->id,
        'city'        => 'Jakarta',
        'country'     => 'Indonesia',
        'searched_at' => now(),
    ]);

    expect($history->user)->not->toBeNull();
    expect($history->user->id)->toBe($user->id);
    expect($history->user()->getRelated())->toBeInstanceOf(User::class);
});

// ── 4. User memiliki relationship ke SearchHistory ──────────
test('User memiliki relationship hasMany SearchHistory', function () {
    $user = User::factory()->create();

    SearchHistory::create(['user_id' => $user->id, 'city' => 'Bali',     'searched_at' => now()]);
    SearchHistory::create(['user_id' => $user->id, 'city' => 'Surabaya', 'searched_at' => now()]);

    expect($user->searchHistories)->toHaveCount(2);
    expect($user->searchHistories()->getRelated())->toBeInstanceOf(SearchHistory::class);
});

// ── 5. user_id foreign key bekerja ──────────────────────────
test('SearchHistory tidak dapat dibuat tanpa user yang valid', function () {
    $this->expectException(\Illuminate\Database\QueryException::class);

    SearchHistory::create([
        'user_id'     => 999999, // user tidak ada
        'city'        => 'London',
        'searched_at' => now(),
    ]);
});

// ── 6. Cascade delete bekerja ───────────────────────────────
test('SearchHistory terhapus otomatis saat user dihapus (cascade)', function () {
    $user = User::factory()->create();

    SearchHistory::create(['user_id' => $user->id, 'city' => 'Yogyakarta', 'searched_at' => now()]);
    SearchHistory::create(['user_id' => $user->id, 'city' => 'Medan',      'searched_at' => now()]);

    $userId = $user->id;
    $user->delete();

    $this->assertDatabaseMissing('search_histories', ['user_id' => $userId]);
    expect(SearchHistory::where('user_id', $userId)->count())->toBe(0);
});

// ── 7. Tidak ada unique constraint — user boleh search kota sama ──
test('user boleh menyimpan riwayat kota yang sama lebih dari sekali', function () {
    $user = User::factory()->create();

    SearchHistory::create(['user_id' => $user->id, 'city' => 'Bandung', 'searched_at' => now()]);
    SearchHistory::create(['user_id' => $user->id, 'city' => 'Bandung', 'searched_at' => now()]);
    SearchHistory::create(['user_id' => $user->id, 'city' => 'Bandung', 'searched_at' => now()]);

    $count = SearchHistory::where('user_id', $user->id)->where('city', 'Bandung')->count();
    expect($count)->toBe(3);
});

// ── 8. Scope forUser bekerja ─────────────────────────────────
test('scopeForUser hanya mengembalikan riwayat milik user tertentu', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    SearchHistory::create(['user_id' => $userA->id, 'city' => 'Solo',   'searched_at' => now()]);
    SearchHistory::create(['user_id' => $userA->id, 'city' => 'Malang', 'searched_at' => now()]);
    SearchHistory::create(['user_id' => $userB->id, 'city' => 'Makassar','searched_at' => now()]);

    $historiesA = SearchHistory::forUser($userA->id)->get();
    $historiesB = SearchHistory::forUser($userB->id)->get();

    expect($historiesA)->toHaveCount(2);
    expect($historiesB)->toHaveCount(1);

    // User A tidak melihat data User B
    expect($historiesA->pluck('city')->contains('Makassar'))->toBeFalse();
});

// ── 9. Riwayat diurutkan dari terbaru ───────────────────────
test('riwayat diurutkan dari searched_at terbaru', function () {
    $user = User::factory()->create();

    SearchHistory::create(['user_id' => $user->id, 'city' => 'Pertama', 'searched_at' => now()->subMinutes(10)]);
    SearchHistory::create(['user_id' => $user->id, 'city' => 'Kedua',   'searched_at' => now()->subMinutes(5)]);
    SearchHistory::create(['user_id' => $user->id, 'city' => 'Ketiga',  'searched_at' => now()]);

    $histories = SearchHistory::forUser($user->id)->get();
    expect($histories->first()->city)->toBe('Ketiga');  // terbaru di atas
    expect($histories->last()->city)->toBe('Pertama');  // terlama di bawah
});

// ── 10. FavoriteCity relationship tidak regression ───────────
test('User::favoriteCities() relationship tidak terpengaruh oleh SearchHistory model', function () {
    $user = User::factory()->create();

    FavoriteCity::create(['user_id' => $user->id, 'city' => 'Semarang', 'country' => 'Indonesia']);
    SearchHistory::create(['user_id' => $user->id, 'city' => 'Semarang', 'searched_at' => now()]);

    // Keduanya independen
    expect($user->favoriteCities)->toHaveCount(1);
    expect($user->searchHistories)->toHaveCount(1);
    expect($user->favoriteCities()->getRelated())->toBeInstanceOf(FavoriteCity::class);
    expect($user->searchHistories()->getRelated())->toBeInstanceOf(SearchHistory::class);
});
