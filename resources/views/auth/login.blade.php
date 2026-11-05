{{-- Halaman Login --}}
<x-layouts.auth title="Masuk — {{ config('app.name') }}">

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
                            d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white">Selamat Datang</h1>
                <p class="text-white/50 text-sm mt-1">Masuk ke akun kamu</p>
            </div>

            {{-- Flash status (dari logout / register redirect) --}}
            @if(session('status'))
                <div class="mb-5 p-3.5 rounded-xl bg-green-500/15 border border-green-500/25 text-green-300 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Form Login --}}
            <form action="{{ route('auth.login.submit') }}" method="POST" class="space-y-5">
                @csrf

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
                        autofocus
                        class="glass-input w-full px-4 py-3 rounded-xl text-sm
                               @error('email') ring-2 ring-rose-400/60 @enderror"
                    >
                    {{--
                        Error di sini mencakup baik "email salah" maupun "password salah"
                        dengan pesan yang sama — mencegah user enumeration attack.
                    --}}
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
                        placeholder="Password kamu"
                        autocomplete="current-password"
                        :hasError="$errors->has('password')"
                    />
                    @error('password')
                        <p class="mt-1.5 text-xs text-rose-300 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 8v4m0 4h.01"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Remember Me --}}
                <div class="flex items-center gap-3">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        class="w-4 h-4 rounded border-white/30 bg-white/10 text-blue-400
                               focus:ring-white/20 cursor-pointer"
                    >
                    <label for="remember" class="text-sm text-white/60 cursor-pointer select-none">
                        Ingat saya
                    </label>
                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    class="w-full py-3.5 bg-white/20 hover:bg-white/30 border border-white/20
                           text-white font-semibold rounded-xl transition-all duration-200
                           focus:outline-none focus:ring-2 focus:ring-white/30"
                >
                    Masuk
                </button>
            </form>

            {{-- Link ke register --}}
            <p class="mt-6 text-center text-sm text-white/50">
                Belum punya akun?
                <a href="{{ route('auth.register') }}"
                   class="text-white/80 hover:text-white font-medium transition-colors">
                    Daftar sekarang
                </a>
            </p>

        </div>

    </div>

</x-layouts.auth>
