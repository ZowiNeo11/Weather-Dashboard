<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * AuthController
 *
 * Menangani semua alur authentication:
 *   - Tampilkan form register / login
 *   - Proses register, login, logout
 *
 * Prinsip:
 *   - Tidak ada logika bisnis di sini selain auth flow
 *   - User ID selalu diambil dari Auth::id(), TIDAK dari request
 *   - Password selalu di-hash Laravel (cast 'hashed' di User model)
 *   - CSRF protection via @csrf pada setiap form
 */
class AuthController extends Controller
{
    // =========================================================
    // REGISTER
    // =========================================================

    /**
     * Tampilkan halaman register.
     * Redirect ke dashboard jika sudah login.
     */
    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    /**
     * Proses registrasi user baru.
     *
     * Flow:
     *   1. Validasi input
     *   2. Buat user (password di-hash otomatis via cast 'hashed')
     *   3. Login langsung
     *   4. Regenerate session (cegah session fixation)
     *   5. Redirect ke dashboard
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'name.required'      => 'Nama wajib diisi.',
            'email.required'     => 'Email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'email.unique'       => 'Email sudah digunakan akun lain.',
            'password.required'  => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min'       => 'Password minimal 8 karakter.',
        ]);

        // Buat user — password di-hash otomatis karena cast 'hashed' di User model
        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => $validated['password'],
        ]);

        // Login langsung setelah register
        Auth::login($user);

        // Regenerate session token untuk mencegah session fixation attack
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Selamat datang, ' . $user->name . '! Akun berhasil dibuat.');
    }

    // =========================================================
    // LOGIN
    // =========================================================

    /**
     * Tampilkan halaman login.
     * Redirect ke dashboard jika sudah login.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Proses login user.
     *
     * Security:
     *   - Pesan error sengaja TIDAK membedakan "email salah" vs "password salah"
     *     untuk mencegah user enumeration attack
     *   - Regenerate session setelah login berhasil
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            // Sengaja tidak membedakan email vs password salah
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Email atau password yang dimasukkan salah.',
                ]);
        }

        // Regenerate session untuk mencegah session fixation
        $request->session()->regenerate();

        // Redirect ke halaman yang diminta sebelumnya (intended),
        // atau fallback ke dashboard
        return redirect()
            ->intended(route('dashboard'))
            ->with('status', 'Selamat datang kembali, ' . Auth::user()->name . '!');
    }

    // =========================================================
    // LOGOUT
    // =========================================================

    /**
     * Logout user.
     *
     * Flow:
     *   1. Logout dari Auth guard
     *   2. Invalidate session (hapus semua data session)
     *   3. Regenerate CSRF token
     *   4. Redirect ke halaman login
     *
     * Harus menggunakan POST untuk mencegah CSRF via link/image.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        // Invalidate session agar tidak bisa di-reuse
        $request->session()->invalidate();

        // Generate CSRF token baru
        $request->session()->regenerateToken();

        return redirect()
            ->route('auth.login')
            ->with('status', 'Anda telah berhasil logout.');
    }
}
