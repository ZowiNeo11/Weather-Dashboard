{{-- Halaman Register --}}
<x-layouts.auth title="Daftar Akun — {{ config('app.name') }}">

    <div class="w-full max-w-md">

        {{-- Card glassmorphism --}}
        <div class="glass-card p-8 weather-enter">

            {{-- Header --}}
            <div class="text-center mb-8">
                <div class="w-14 h-14 rounded-2xl bg-white/15 border border-white/20
                            flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-white" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round"
                            d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white">Buat Akun</h1>
                <p class="text-white/50 text-sm mt-1">Daftar untuk mengakses fitur personal</p>
            </div>

            {{-- Flash status --}}
            @if(session('status'))
                <div class="mb-5 p-3.5 rounded-xl bg-green-500/15 border border-green-500/25 text-green-300 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Form Register --}}
            <form action="{{ route('auth.register') }}" method="POST" class="space-y-5">
                @csrf

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-white/70 mb-1.5">
                        Nama Lengkap
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Nama kamu"
                        autocomplete="name"
                        autofocus
                        class="glass-input w-full px-4 py-3 rounded-xl text-sm
                               @error('name') ring-2 ring-rose-400/60 @enderror"
                    >
                    @error('name')
                        <p class="mt-1.5 text-xs text-rose-300 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 8v4m0 4h.01"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-white/70 mb-1.5">
                        Alamat Email
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="kamu@email.com"
                        autocomplete="email"
                        class="glass-input w-full px-4 py-3 rounded-xl text-sm
                               @error('email') ring-2 ring-rose-400/60 @enderror"
                    >
                    @error('email')
                        <p class="mt-1.5 text-xs text-rose-300 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 8v4m0 4h.01"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Password + Show/Hide Toggle --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-white/70 mb-1.5">
                        Password
                    </label>
                    <x-password-input
                        id="password"
                        name="password"
                        placeholder="Minimal 8 karakter"
                        autocomplete="new-password"
                        :hasError="$errors->has('password')"
                    />
                    @error('password')
                        <p class="mt-1.5 text-xs text-rose-300 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 8v4m0 4h.01"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Password Confirmation + Show/Hide Toggle (independen) --}}
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-white/70 mb-1.5">
                        Konfirmasi Password
                    </label>
                    <x-password-input
                        id="password_confirmation"
                        name="password_confirmation"
                        placeholder="Ulangi password"
                        autocomplete="new-password"
                        :hasError="false"
                    />
                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    class="w-full py-3.5 bg-white/20 hover:bg-white/30 border border-white/20
                           text-white font-semibold rounded-xl transition-all duration-200
                           focus:outline-none focus:ring-2 focus:ring-white/30 mt-2"
                >
                    Buat Akun
                </button>
            </form>

            {{-- Link ke login --}}
            <p class="mt-6 text-center text-sm text-white/50">
                Sudah punya akun?
                <a href="{{ route('auth.login') }}"
                   class="text-white/80 hover:text-white font-medium transition-colors">
                    Masuk di sini
                </a>
            </p>

        </div>

    </div>

</x-layouts.auth>
