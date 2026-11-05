{{--
    Komponen: Hourly Card
    Satu kolom dalam scrollable hourly forecast.

    Props: $hour (array dari WeatherData->hourly), $active (bool)
--}}
@props(['hour' => [], 'active' => false])

<div
    data-hour="{{ $hour['hour'] ?? 0 }}"
    class="flex flex-col items-center gap-1.5 px-3 py-3 rounded-xl flex-shrink-0 w-16
           transition-all duration-200 cursor-default
           {{ $active
               ? 'bg-white/20 border border-white/30'
               : 'bg-white/5 border border-white/8 hover:bg-white/10' }}"
>
    {{-- Waktu --}}
    <span class="text-xs font-medium {{ $active ? 'text-white' : 'text-white/60' }}">
        {{ $hour['time'] ?? '--:--' }}
    </span>

    {{-- Ikon cuaca --}}
    <img src="{{ $hour['icon'] ?? '' }}"
         alt="{{ $hour['condition'] ?? '' }}"
         class="w-8 h-8 object-contain"
         loading="lazy">

    {{-- Suhu --}}
    <span class="text-sm font-bold text-white">{{ $hour['temp_c'] ?? '--' }}°</span>

    {{-- Peluang hujan (tampil jika > 0) --}}
    @if(($hour['chance_rain'] ?? 0) > 0)
        <div class="flex items-center gap-0.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5 text-blue-300" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
            </svg>
            <span class="text-xs text-blue-300">{{ $hour['chance_rain'] }}%</span>
        </div>
    @else
        <span class="text-xs text-white/30">—</span>
    @endif
</div>
