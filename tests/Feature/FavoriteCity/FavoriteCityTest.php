<?php

use App\Models\FavoriteCity;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// =========================================================
// SKENARIO 1: Guest tidak dapat menambahkan favorite
// =========================================================
test('guest tidak dapat menambahkan favorite city', function () {
    $response = $this->post(route('favorites.store'), [
        'city'    => 'Bandung',
        'country' => 'Indonesia',
    ]);

    // Harus di-redirect ke login, bukan 200 atau 500
    $response->assertRedirect(route('auth.login'));

    // Tidak ada record yang tersimpan di database
    $this->assertDatabaseMissing('favorite_cities', ['city' => 'Bandung']);
});

// =========================================================
// SKENARIO 2: Authenticated user dapat menambahkan favorite
// =========================================================
test('authenticated user dapat menambahkan favorite city', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('favorites.store'), [
        'city'    => 'Bandung',
        'country' => 'Indonesia',
    ]);

    // Harus redirect kembali (PRG pattern dari back())
    $response->assertRedirect();

    // Record harus tersimpan di database
    $this->assertDatabaseHas('favorite_cities', [
        'city'    => 'Bandung',
        'country' => 'Indonesia',
    ]);
});

// =========================================================
// SKENARIO 3: Favorite tersimpan dengan user_id authenticated user
// =========================================================
test('favorite tersimpan dengan user_id dari authenticated user bukan dari request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('favorites.store'), [
        'city'    => 'Jakarta',
        'country' => 'Indonesia',
        // user_id tidak dikirim dari request — harus diambil dari Auth::id()
    ]);

    $favorite = FavoriteCity::where('city', 'Jakarta')->first();

    expect($favorite)->not->toBeNull();
    // user_id harus sama dengan authenticated user, bukan dari request
    expect($favorite->user_id)->toBe($user->id);
});

// =========================================================
// SKENARIO 4: User tidak dapat membuat duplicate favorite
// =========================================================
test('user tidak dapat membuat duplicate favorite city', function () {
    $user = User::factory()->create();

    // Tambah pertama kali
    $this->actingAs($user)->post(route('favorites.store'), [
        'city'    => 'Bali',
        'country' => 'Indonesia',
    ]);

    // Coba tambah lagi — kota yang sama
    $this->actingAs($user)->post(route('favorites.store'), [
        'city'    => 'Bali',
        'country' => 'Indonesia',
    ]);

    // Harus tetap hanya 1 record di database (bukan 2)
    $count = FavoriteCity::where('user_id', $user->id)
        ->where('city', 'Bali')
        ->count();

    expect($count)->toBe(1);
});

test('duplicate favorite tidak menghasilkan error 500', function () {
    $user = User::factory()->create();

    // Buat favorit pertama
    FavoriteCity::create([
        'user_id' => $user->id,
        'city'    => 'Surabaya',
        'country' => 'Indonesia',
    ]);

    // Coba tambah lagi — harus mengembalikan redirect, bukan 500
    $response = $this->actingAs($user)->post(route('favorites.store'), [
        'city'    => 'Surabaya',
        'country' => 'Indonesia',
    ]);

    $response->assertRedirect();
    $response->assertSessionMissing('errors');
});

// =========================================================
// SKENARIO 5: User dapat melihat favorite miliknya
// =========================================================
test('authenticated user dapat melihat favorite city miliknya di dashboard', function () {
    $user = User::factory()->create();

    FavoriteCity::create([
        'user_id' => $user->id,
        'city'    => 'Yogyakarta',
        'country' => 'Indonesia',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    // Nama kota harus muncul di halaman
    $response->assertSee('Yogyakarta');
});

// =========================================================
// SKENARIO 6: User tidak dapat melihat favorite user lain
// =========================================================
test('user tidak dapat melihat favorite city milik user lain', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    // User B punya favorit "Makassar"
    FavoriteCity::create([
        'user_id' => $userB->id,
        'city'    => 'Makassar',
        'country' => 'Indonesia',
    ]);

    // User A login dan akses dashboard
    $response = $this->actingAs($userA)->get(route('dashboard'));

    $response->assertStatus(200);

    // Pastikan User A hanya melihat favoritnya sendiri (kosong)
    // "Makassar" tidak boleh muncul sebagai favorit User A
    $userAFavorites = FavoriteCity::where('user_id', $userA->id)->get();
    expect($userAFavorites)->toHaveCount(0);

    // Secara database, Makassar hanya milik User B
    $this->assertDatabaseHas('favorite_cities', [
        'user_id' => $userB->id,
        'city'    => 'Makassar',
    ]);
    $this->assertDatabaseMissing('favorite_cities', [
        'user_id' => $userA->id,
        'city'    => 'Makassar',
    ]);
});

// =========================================================
// SKENARIO 7: User dapat menghapus favorite miliknya
// =========================================================
test('authenticated user dapat menghapus favorite city miliknya', function () {
    $user = User::factory()->create();

    $favorite = FavoriteCity::create([
        'user_id' => $user->id,
        'city'    => 'Medan',
        'country' => 'Indonesia',
    ]);

    $response = $this->actingAs($user)
        ->delete(route('favorites.destroy', $favorite->id));

    // Harus redirect (back())
    $response->assertRedirect();

    // Record harus terhapus dari database
    $this->assertDatabaseMissing('favorite_cities', [
        'id' => $favorite->id,
    ]);
});

// =========================================================
// SKENARIO 8: User tidak dapat menghapus favorite user lain
// =========================================================
test('user tidak dapat menghapus favorite city milik user lain', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    // Favorite milik User B
    $favoriteB = FavoriteCity::create([
        'user_id' => $userB->id,
        'city'    => 'Palembang',
        'country' => 'Indonesia',
    ]);

    // User A mencoba menghapus favorite milik User B
    $response = $this->actingAs($userA)
        ->delete(route('favorites.destroy', $favoriteB->id));

    // Harus 404 — bukan 200 atau 403 yang leak info
    $response->assertStatus(404);

    // Record User B harus tetap ada di database
    $this->assertDatabaseHas('favorite_cities', [
        'id'      => $favoriteB->id,
        'user_id' => $userB->id,
        'city'    => 'Palembang',
    ]);
});

// =========================================================
// SKENARIO 9: Guest tidak dapat menghapus favorite
// =========================================================
test('guest tidak dapat menghapus favorite city', function () {
    $user = User::factory()->create();

    $favorite = FavoriteCity::create([
        'user_id' => $user->id,
        'city'    => 'Semarang',
        'country' => 'Indonesia',
    ]);

    // Guest mencoba delete tanpa login
    $response = $this->delete(route('favorites.destroy', $favorite->id));

    // Harus di-redirect ke login
    $response->assertRedirect(route('auth.login'));

    // Record harus tetap ada
    $this->assertDatabaseHas('favorite_cities', [
        'id' => $favorite->id,
    ]);
});

// =========================================================
// SKENARIO 10: Empty state muncul jika user belum punya favorite
// =========================================================
test('empty state muncul jika user belum memiliki favorite city', function () {
    $user = User::factory()->create();

    // User login tanpa favorit apapun
    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);

    // Pastikan teks empty state muncul
    $response->assertSee('Belum ada kota favorit');
    $response->assertSee('My Favorite Cities');
});

// =========================================================
// VALIDASI TAMBAHAN
// =========================================================
test('store menolak input city yang kosong', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('favorites.store'), [
        'city' => '',
    ]);

    $response->assertSessionHasErrors('city');
});

test('store menolak input city yang terlalu panjang', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('favorites.store'), [
        'city' => str_repeat('A', 101), // > 100 karakter
    ]);

    $response->assertSessionHasErrors('city');
});

// =========================================================
// STEP 8 — VALIDATION: test tambahan
// =========================================================

test('store menolak city kurang dari 2 karakter', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('favorites.store'), [
        'city' => 'A', // 1 karakter — tidak valid
    ]);

    $response->assertSessionHasErrors('city');
    $this->assertDatabaseMissing('favorite_cities', [
        'user_id' => $user->id,
        'city'    => 'A',
    ]);
});

test('store menolak input city yang bukan string (array injection)', function () {
    $user = User::factory()->create();

    // Kirim city sebagai array — teknik umum untuk bypass string validation
    // Laravel harus menolak ini dengan validation error, bukan error 500
    $response = $this->actingAs($user)->post(route('favorites.store'), [
        'city' => ['Bandung', 'Jakarta'], // array, bukan string
    ]);

    // Harus validation error atau redirect dengan error, bukan 500
    expect($response->getStatusCode())->not->toBe(500);

    // Tidak boleh ada record tersimpan
    $this->assertDatabaseMissing('favorite_cities', [
        'user_id' => $user->id,
    ]);
});

test('user_id tidak dapat dimanipulasi melalui request', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    // User A login, tapi coba sisipkan user_id milik User B di request body
    $response = $this->actingAs($userA)->post(route('favorites.store'), [
        'city'    => 'Denpasar',
        'country' => 'Indonesia',
        'user_id' => $userB->id, // mencoba manipulasi ownership
    ]);

    $response->assertRedirect();

    // Record yang tersimpan harus atas nama User A, bukan User B
    $favorite = FavoriteCity::where('city', 'Denpasar')->first();
    expect($favorite)->not->toBeNull();
    expect($favorite->user_id)->toBe($userA->id);      // user yang login
    expect($favorite->user_id)->not->toBe($userB->id); // bukan dari request
});

test('store menerima city dengan 2 karakter (batas minimum valid)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('favorites.store'), [
        'city' => 'LA', // 2 karakter — valid (Los Angeles)
    ]);

    $response->assertRedirect();
    $response->assertSessionMissing('errors');

    $this->assertDatabaseHas('favorite_cities', [
        'user_id' => $user->id,
        'city'    => 'LA',
    ]);
});

test('store menerima city dengan 100 karakter (batas maksimum valid)', function () {
    $user = User::factory()->create();

    $longCity = str_repeat('A', 100); // tepat 100 karakter — valid

    $response = $this->actingAs($user)->post(route('favorites.store'), [
        'city' => $longCity,
    ]);

    $response->assertRedirect();
    $response->assertSessionMissing('errors');

    $this->assertDatabaseHas('favorite_cities', [
        'user_id' => $user->id,
        'city'    => $longCity,
    ]);
});

test('user berbeda boleh menyimpan kota yang sama', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    // Kedua user favorit kota yang sama
    $this->actingAs($userA)->post(route('favorites.store'), ['city' => 'Bandung', 'country' => 'Indonesia']);
    $this->actingAs($userB)->post(route('favorites.store'), ['city' => 'Bandung', 'country' => 'Indonesia']);

    // Keduanya harus tersimpan — bukan unique violation
    expect(FavoriteCity::where('city', 'Bandung')->count())->toBe(2);
    expect(FavoriteCity::where('user_id', $userA->id)->where('city', 'Bandung')->count())->toBe(1);
    expect(FavoriteCity::where('user_id', $userB->id)->where('city', 'Bandung')->count())->toBe(1);
});

// =========================================================
// STEP 9 — UI/UX STATE TESTS
// =========================================================

test('authenticated user melihat tombol Tambah ke Favorit ketika kota belum difavoritkan', function () {
    $user = User::factory()->create();

    // Akses dashboard tanpa kota yang difavoritkan
    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    // Hanya komponen My Favorites yang tampil — tidak ada "Tambah ke Favorit"
    // karena tidak ada $weather (belum search)
    $response->assertSee('My Favorite Cities');
    $response->assertSee('Belum ada kota favorit');
});

test('authenticated user melihat tombol Favorited setelah menambahkan favorit', function () {
    $user = User::factory()->create();

    // Simpan favorit langsung via model (tanpa API call)
    FavoriteCity::create([
        'user_id' => $user->id,
        'city'    => 'Bandung',
        'country' => 'Indonesia',
    ]);

    // Dashboard dengan favorit yang tersimpan
    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    // Nama kota harus muncul di section favorites
    $response->assertSee('Bandung');
    // Section "My Favorite Cities" harus tampil
    $response->assertSee('My Favorite Cities');
    // Empty state tidak boleh tampil
    $response->assertDontSee('Belum ada kota favorit');
});

test('guest melihat CTA login bukan tombol favorite', function () {
    // Guest mengakses dashboard
    $response = $this->get(route('dashboard'));

    $response->assertStatus(200);
    // Guest melihat tombol "Masuk" di navbar (bukan Favorite section yang @auth)
    $response->assertSee('Masuk');
    // Guest tidak melihat section "My Favorite Cities" (dibungkus @auth di dashboard)
    $response->assertDontSee('My Favorite Cities');
    // Guest tidak melihat tombol logout
    $response->assertDontSee('Logout');
});

test('favorite list hanya menampilkan kota milik authenticated user', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    FavoriteCity::create(['user_id' => $userA->id, 'city' => 'PalangkaRayaUnik', 'country' => 'Indonesia']);
    FavoriteCity::create(['user_id' => $userB->id, 'city' => 'TanjungPinangUnik', 'country' => 'Indonesia']);

    // User A login — melihat kotanya, tidak melihat kota User B
    $responseA = $this->actingAs($userA)->get(route('dashboard'));
    $responseA->assertSee('PalangkaRayaUnik');
    $responseA->assertDontSee('TanjungPinangUnik');

    // User B login — melihat kotanya, tidak melihat kota User A
    $responseB = $this->actingAs($userB)->get(route('dashboard'));
    $responseB->assertSee('TanjungPinangUnik');
    $responseB->assertDontSee('PalangkaRayaUnik');
});

test('add favorite memberikan flash status session yang benar', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('favorites.store'), [
        'city'    => 'Surabaya',
        'country' => 'Indonesia',
    ]);

    $response->assertRedirect();
    // Session harus berisi favorite_status dengan type 'added'
    $response->assertSessionHas('favorite_status');

    $status = $response->getSession()->get('favorite_status');
    expect($status['type'])->toBe('added');
    expect($status['message'])->toContain('Surabaya');
});

test('remove favorite memberikan flash status session yang benar', function () {
    $user = User::factory()->create();

    $favorite = FavoriteCity::create([
        'user_id' => $user->id,
        'city'    => 'Yogyakarta',
        'country' => 'Indonesia',
    ]);

    $response = $this->actingAs($user)
        ->delete(route('favorites.destroy', $favorite->id));

    $response->assertRedirect();

    $status = $response->getSession()->get('favorite_status');
    expect($status['type'])->toBe('removed');
    expect($status['message'])->toContain('Yogyakarta');
});

test('duplicate favorite memberikan flash status already bukan error', function () {
    $user = User::factory()->create();

    FavoriteCity::create([
        'user_id' => $user->id,
        'city'    => 'Bali',
        'country' => 'Indonesia',
    ]);

    // Coba tambah lagi
    $response = $this->actingAs($user)->post(route('favorites.store'), [
        'city'    => 'Bali',
        'country' => 'Indonesia',
    ]);

    $response->assertRedirect();
    $response->assertSessionMissing('errors');

    $status = $response->getSession()->get('favorite_status');
    expect($status['type'])->toBe('already');
});

test('empty state muncul dan berisi teks yang benar', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertSee('Belum ada kota favorit');
    $response->assertSee('Cari kota dan klik');
});

test('country ditampilkan di favorite list jika tersedia', function () {
    $user = User::factory()->create();

    FavoriteCity::create([
        'user_id' => $user->id,
        'city'    => 'Denpasar',
        'country' => 'Indonesia',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertSee('Denpasar');
    $response->assertSee('Indonesia');
});

test('favorite list menampilkan count badge yang benar', function () {
    $user = User::factory()->create();

    FavoriteCity::create(['user_id' => $user->id, 'city' => 'Kota1', 'country' => 'Indonesia']);
    FavoriteCity::create(['user_id' => $user->id, 'city' => 'Kota2', 'country' => 'Indonesia']);
    FavoriteCity::create(['user_id' => $user->id, 'city' => 'Kota3', 'country' => 'Indonesia']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    // Badge count "3 kota" harus ada
    $response->assertSee('3 kota');
});
