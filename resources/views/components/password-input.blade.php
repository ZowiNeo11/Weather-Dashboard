{{--
    Komponen: Password Input dengan Show/Hide Toggle
    Digunakan di halaman login dan register.

    Props:
    - $id          : string  — id unik field (dipakai juga untuk aria-labelledby)
    - $name        : string  — name attribute untuk form submission
    - $placeholder : string  — placeholder text
    - $autocomplete: string  — autocomplete hint (current-password / new-password)
    - $hasError    : bool    — apakah field ini memiliki validation error

    Cara kerja toggle:
    - Tombol eye/eye-slash di dalam wrapper relative
    - Klik mengubah type antara "password" ↔ "text" via JS (data-password-toggle)
    - aria-label pada tombol diupdate oleh JS saat state berubah
    - Password TIDAK disimpan di mana pun — hanya type input yang berubah
    - Default selalu type="password" saat halaman dibuka
--}}
@props([
    'id'           => 'password',
    'name'         => 'password',
    'placeholder'  => 'Password',
    'autocomplete' => 'current-password',
    'hasError'     => false,
])

<div class="relative">
    <input
        type="password"
        id="{{ $id }}"
        name="{{ $name }}"
        placeholder="{{ $placeholder }}"
        autocomplete="{{ $autocomplete }}"
        {{-- data-password-toggle: target ID yang akan di-toggle oleh JS --}}
        data-password-field="{{ $id }}"
        class="glass-input w-full pl-4 pr-11 py-3 rounded-xl text-sm
               {{ $hasError ? 'ring-2 ring-rose-400/60' : '' }}"
    >

    {{--
        Tombol toggle show/hide.
        - type="button" agar tidak submit form secara tidak sengaja
        - aria-label dinamis ("Tampilkan password" / "Sembunyikan password")
          — diupdate oleh JS, nilai awal mencerminkan state default (hidden)
        - aria-controls menunjuk ke field yang dikontrol
        - data-password-toggle menandai ini sebagai tombol toggle untuk JS
    --}}
    <button
        type="button"
        data-password-toggle="{{ $id }}"
        aria-label="Tampilkan password"
        aria-controls="{{ $id }}"
        title="Tampilkan password"
        class="absolute inset-y-0 right-0 flex items-center px-3.5
               text-white/40 hover:text-white/80 transition-colors duration-200
               focus:outline-none focus-visible:ring-2 focus-visible:ring-white/30
               focus-visible:rounded-r-xl"
    >
        {{-- Ikon EYE — tampil saat password tersembunyi (state default) --}}
        <svg data-icon-show
             xmlns="http://www.w3.org/2000/svg"
             class="w-4 h-4"
             fill="none" viewBox="0 0 24 24"
             stroke="currentColor" stroke-width="2"
             aria-hidden="true">
            <path stroke-linecap="round"
                d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/>
            <path stroke-linecap="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>

        {{-- Ikon EYE-SLASH — tampil saat password terlihat --}}
        <svg data-icon-hide
             xmlns="http://www.w3.org/2000/svg"
             class="w-4 h-4 hidden"
             fill="none" viewBox="0 0 24 24"
             stroke="currentColor" stroke-width="2"
             aria-hidden="true">
            <path stroke-linecap="round"
                d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
        </svg>
    </button>
</div>
