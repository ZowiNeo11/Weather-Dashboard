{{--
    Komponen: Forecast Day (7-day)
    Satu baris prakiraan harian.

    Props: $day (array dari WeatherData->forecast), $active (bool)
--}}
@props(['day' => [], 'active' => false])

<div class="flex items-center gap-2 sm:gap-3 py-2.5 px-3 rounded-xl transition-all
            {{ $active ? 'bg-white/15 border border-white/20' : 'hover:bg-white/8' }}">

    {{-- Nama hari — lebar min agar tidak terlalu sempit di mobile --}}
    <span class="text-sm font-medium min-w-[3.5rem] flex-shrink-0
                 {{ $active ? 'text-white' : 'text-white/70' }}">
        {{ $day['day_name'] ?? '—' }}
    </span>

    {{-- Ikon --}}
    <img src="{{ $day['icon'] ?? '' }}"
         alt="{{ $day['condition'] ?? '' }}"
         class="w-8 h-8 object-contain flex-shrink-0"
         loading="lazy">

    {{--
        Kondisi — tampil di sm+ (≥640px).
        Sebelumnya 'hidden sm:block' yang menyebabkan teks hilang
        saat forecast card di-render pada laptop narrow.
        Dipertahankan hidden di bawah sm karena mobile sudah bagus.
    --}}
    <span class="text-xs text-white/50 flex-1 truncate hidden sm:block min-w-0">
        {{ $day['condition'] ?? '' }}
    </span>

    {{-- Spacer saat kondisi tersembunyi (mobile) --}}
    <span class="flex-1 sm:hidden"></span>

    {{-- Chance of rain --}}
    @if(($day['chance_rain'] ?? 0) > 0)
        <div class="flex items-center gap-1 flex-shrink-0 w-11 justify-end">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-blue-300" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
            </svg>
            <span class="text-xs text-blue-300">{{ $day['chance_rain'] }}%</span>
        </div>
    @else
        <div class="w-11 flex-shrink-0"></div>
    @endif

    {{-- Range suhu — nowrap agar tidak terpotong --}}
    <div class="flex items-center gap-1 flex-shrink-0 whitespace-nowrap">
        <span class="text-sm font-bold text-white">{{ $day['max_c'] ?? '--' }}°</span>
        <span class="text-xs text-white/40">/</span>
        <span class="text-sm text-white/50">{{ $day['min_c'] ?? '--' }}°</span>
    </div>
</div>
