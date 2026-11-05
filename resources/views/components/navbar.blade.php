{{-- Komponen: Navbar utama (glassmorphism style) --}}
<nav class="sticky top-0 z-50 border-b border-white/10"
     style="background: rgba(0,0,0,0.25); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14">

            {{-- Logo --}}
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                <span class="font-semibold text-white text-sm tracking-tight">
                    Weather Dashboard
                </span>
            </a>

            {{-- Kanan: user info + waktu --}}
            <div class="flex items-center gap-3">

                {{-- Info tanggal & jam (WIB) --}}
                <div class="hidden sm:block text-right">
                    <p class="text-xs font-medium text-white/90">
                        {{ now()->setTimezone('Asia/Jakarta')->translatedFormat('l, d F Y') }}
                    </p>
                    <p class="text-xs text-white/50">
                        <span id="navbar-clock">
                            {{ now()->setTimezone('Asia/Jakarta')->format('H:i') }} WIB
                        </span>
                        — Bandung, Indonesia
                    </p>
                </div>

                {{-- User menu (hanya tampil jika sudah login) --}}
                @auth
                    <div class="flex items-center gap-2 pl-3 border-l border-white/15">
                        {{-- Avatar inisial --}}
                        <div class="w-8 h-8 rounded-full bg-white/20 border border-white/25
                                    flex items-center justify-center flex-shrink-0"
                             title="{{ auth()->user()->name }} — {{ auth()->user()->email }}">
                            <span class="text-xs font-bold text-white leading-none">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                        </div>

                        {{-- Nama user (hidden di mobile kecil) --}}
                        <div class="hidden md:block text-left">
                            <p class="text-xs font-semibold text-white/90 leading-tight">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="text-xs text-white/40 leading-tight truncate max-w-32">
                                {{ auth()->user()->email }}
                            </p>
                        </div>

                        {{-- Tombol Logout (POST form untuk CSRF protection) --}}
                        <form action="{{ route('auth.logout') }}" method="POST" class="ml-1">
                            @csrf
                            <button type="submit"
                                title="Logout"
                                class="w-8 h-8 flex items-center justify-center rounded-lg
                                       bg-white/10 hover:bg-rose-500/30 border border-white/10
                                       hover:border-rose-400/30 text-white/60 hover:text-rose-300
                                       transition-all duration-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round"
                                        d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                @endauth

                {{-- Tombol Login (hanya tampil jika belum login) --}}
                @guest
                    <a href="{{ route('auth.login') }}"
                       class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                              bg-white/10 hover:bg-white/20 border border-white/15
                              text-white/70 hover:text-white text-xs font-medium
                              transition-all duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H3.75"/>
                        </svg>
                        Masuk
                    </a>
                @endguest

            </div>

        </div>
    </div>
</nav>
