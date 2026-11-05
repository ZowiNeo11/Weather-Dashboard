<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// =========================================================
// SKENARIO 1: Guest dapat membuka halaman login
// =========================================================
test('guest dapat membuka halaman login', function () {
    $response = $this->get(route('auth.login'));

    $response->assertStatus(200);
    $response->assertViewIs('auth.login');
});

// =========================================================
// SKENARIO 2: Guest dapat membuka halaman register
// =========================================================
test('guest dapat membuka halaman register', function () {
    $response = $this->get(route('auth.register'));

    $response->assertStatus(200);
    $response->assertViewIs('auth.register');
});

// =========================================================
// SKENARIO 3: User dapat register dengan data yang valid
// =========================================================
test('user dapat register dengan data valid', function () {
    $response = $this->post(route('auth.register'), [
        'name'                  => 'Budi Santoso',
        'email'                 => 'budi@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ]);

    // Harus redirect ke dashboard setelah register
    $response->assertRedirect(route('dashboard'));

    // User harus tersimpan di database
    $this->assertDatabaseHas('users', [
        'name'  => 'Budi Santoso',
        'email' => 'budi@example.com',
    ]);

    // User harus langsung ter-authenticate
    $this->assertAuthenticated();
});

// =========================================================
// SKENARIO 4: Password tersimpan dalam bentuk hash (tidak plain text)
// =========================================================
test('password user tersimpan dalam bentuk hash bukan plain text', function () {
    $this->post(route('auth.register'), [
        'name'                  => 'Ani Rahayu',
        'email'                 => 'ani@example.com',
        'password'              => 'rahasiabanget',
        'password_confirmation' => 'rahasiabanget',
    ]);

    $user = User::where('email', 'ani@example.com')->first();

    // Password di database harus berupa hash, bukan plain text
    expect($user->password)->not->toBe('rahasiabanget');

    // Hash harus valid ketika diverifikasi
    expect(Hash::check('rahasiabanget', $user->password))->toBeTrue();
});

// =========================================================
// SKENARIO 5: User dapat login dengan credential yang benar
// =========================================================
test('user dapat login dengan credential benar', function () {
    $user = User::factory()->create([
        'email'    => 'tes@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->post(route('auth.login.submit'), [
        'email'    => 'tes@example.com',
        'password' => 'password123',
    ]);

    // Harus redirect ke dashboard
    $response->assertRedirect(route('dashboard'));

    // User harus ter-authenticate
    $this->assertAuthenticatedAs($user);
});

// =========================================================
// SKENARIO 6: Login gagal dengan credential yang salah
// =========================================================
test('login gagal dengan password yang salah', function () {
    User::factory()->create([
        'email'    => 'sani@example.com',
        'password' => Hash::make('passwordbenar'),
    ]);

    $response = $this->post(route('auth.login.submit'), [
        'email'    => 'sani@example.com',
        'password' => 'passwordsalah',
    ]);

    // Harus kembali ke halaman login dengan error
    $response->assertSessionHasErrors('email');

    // User tidak boleh ter-authenticate
    $this->assertGuest();
});

test('login gagal dengan email yang tidak terdaftar', function () {
    $response = $this->post(route('auth.login.submit'), [
        'email'    => 'tidakada@example.com',
        'password' => 'apapun',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

// =========================================================
// SKENARIO 7: Authenticated user dapat mengakses dashboard
// =========================================================
test('authenticated user dapat mengakses dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertViewIs('dashboard');
});

// =========================================================
// SKENARIO 8: Guest tidak dapat mengakses protected route
// =========================================================
test('guest tidak dapat mengakses route yang dilindungi auth middleware', function () {
    // Route logout adalah contoh protected route (middleware 'auth')
    $response = $this->post(route('auth.logout'));

    // Harus di-redirect ke halaman login
    $response->assertRedirect(route('auth.login'));
});

test('halaman login dan register redirect ke dashboard jika sudah login', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('auth.login'))
         ->assertRedirect(route('dashboard'));

    $this->actingAs($user)->get(route('auth.register'))
         ->assertRedirect(route('dashboard'));
});

// =========================================================
// SKENARIO 9: User dapat logout
// =========================================================
test('authenticated user dapat logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
                     ->post(route('auth.logout'));

    // Harus redirect ke halaman login
    $response->assertRedirect(route('auth.login'));

    // User tidak boleh ter-authenticate setelah logout
    $this->assertGuest();
});

// =========================================================
// SKENARIO 10: Setelah logout, user tidak dapat akses protected route
// =========================================================
test('setelah logout user tidak dapat mengakses protected route', function () {
    $user = User::factory()->create();

    // Login dulu
    $this->actingAs($user);
    $this->assertAuthenticated();

    // Logout
    $this->post(route('auth.logout'));

    // Coba akses protected route
    $response = $this->post(route('auth.logout'));

    // Harus di-redirect ke login, bukan 200/500
    $response->assertRedirect(route('auth.login'));
    $this->assertGuest();
});

// =========================================================
// VALIDASI REGISTER — edge cases
// =========================================================
test('register gagal jika email sudah dipakai', function () {
    User::factory()->create(['email' => 'sudahada@example.com']);

    $response = $this->post(route('auth.register'), [
        'name'                  => 'User Baru',
        'email'                 => 'sudahada@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('register gagal jika password confirmation tidak cocok', function () {
    $response = $this->post(route('auth.register'), [
        'name'                  => 'User Test',
        'email'                 => 'test@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'tidakcocok',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});

test('register gagal jika password kurang dari 8 karakter', function () {
    $response = $this->post(route('auth.register'), [
        'name'                  => 'User Test',
        'email'                 => 'test@example.com',
        'password'              => 'pendek',
        'password_confirmation' => 'pendek',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});
