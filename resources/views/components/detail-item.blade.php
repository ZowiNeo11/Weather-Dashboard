{{--
    Komponen: Detail Item
    Satu baris metrik kecil di dalam glass card (icon + label + value)

    Props: $icon, $label, $value, $unit (opsional), $sub (opsional)
--}}
@props(['icon' => 'thermometer', 'label' => '', 'value' => '—', 'unit' => '', 'sub' => ''])

<div class="flex items-center gap-3 py-2.5 border-b border-white/8 last:border-0">
    {{-- Ikon --}}
    <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0">
        <x-weather-icon :name="$icon" class="w-4 h-4 text-white/70" />
    </div>

    {{-- Label --}}
    <span class="text-sm text-white/60 flex-1">{{ $label }}</span>

    {{-- Nilai --}}
    <div class="text-right">
        <span class="text-sm font-semibold text-white">{{ $value }}</span>
        @if($unit)
            <span class="text-xs text-white/50 ml-0.5">{{ $unit }}</span>
        @endif
        @if($sub)
            <p class="text-xs text-white/40 mt-0.5">{{ $sub }}</p>
        @endif
    </div>
</div>
