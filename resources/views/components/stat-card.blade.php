{{--
    Komponen: Stat Card
    Menampilkan satu metrik cuaca (suhu, kelembaban, angin, dll.)

    Props:
    - $label  : string  — nama metrik (contoh: "Suhu")
    - $value  : string  — nilai metrik (contoh: "28°C")
    - $unit   : string  — satuan opsional (contoh: "km/h")
    - $icon   : string  — nama ikon SVG (sun|wind|droplet|eye|thermometer|gauge)
    - $color  : string  — kelas warna Tailwind (contoh: "blue", "sky", "amber")
    - $trend  : string  — "up" | "down" | null
--}}
@props([
    'label'  => 'Metrik',
    'value'  => '—',
    'unit'   => '',
    'icon'   => 'thermometer',
    'color'  => 'blue',
    'trend'  => null,
])

{{-- Map nama color ke class Tailwind agar PurgeCSS tidak menghapusnya --}}
@php
    $colorMap = [
        'blue'   => ['bg' => 'bg-blue-50',   'icon' => 'text-blue-500',   'border' => 'border-blue-100'],
        'sky'    => ['bg' => 'bg-sky-50',    'icon' => 'text-sky-500',    'border' => 'border-sky-100'],
        'amber'  => ['bg' => 'bg-amber-50',  'icon' => 'text-amber-500',  'border' => 'border-amber-100'],
        'green'  => ['bg' => 'bg-green-50',  'icon' => 'text-green-500',  'border' => 'border-green-100'],
        'purple' => ['bg' => 'bg-purple-50', 'icon' => 'text-purple-500', 'border' => 'border-purple-100'],
        'rose'   => ['bg' => 'bg-rose-50',   'icon' => 'text-rose-500',   'border' => 'border-rose-100'],
    ];
    $colors = $colorMap[$color] ?? $colorMap['blue'];
@endphp

<div class="weather-card flex flex-col gap-4">
    {{-- Header: label & ikon --}}
    <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-slate-500">{{ $label }}</span>

        {{-- Kontainer ikon dengan warna dinamis --}}
        <div class="w-10 h-10 rounded-xl {{ $colors['bg'] }} {{ $colors['border'] }} border
                    flex items-center justify-center flex-shrink-0">
            <x-weather-icon :name="$icon" class="w-5 h-5 {{ $colors['icon'] }}" />
        </div>
    </div>

    {{-- Nilai utama --}}
    <div class="flex items-end gap-1.5">
        <span class="text-3xl font-bold text-slate-800 leading-none">{{ $value }}</span>
        @if($unit)
            <span class="text-sm text-slate-500 pb-0.5">{{ $unit }}</span>
        @endif
    </div>

    {{-- Indikator tren (opsional) --}}
    @if($trend)
        <div class="flex items-center gap-1 text-xs font-medium
                    {{ $trend === 'up' ? 'text-rose-500' : 'text-green-500' }}">
            @if($trend === 'up')
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0110 17z" clip-rule="evenodd" />
                </svg>
                <span>Naik dari kemarin</span>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.638l3.96-4.158a.75.75 0 111.08 1.04l-5.25 5.5a.75.75 0 01-1.08 0l-5.25-5.5a.75.75 0 111.08-1.04l3.96 4.158V3.75A.75.75 0 0110 3z" clip-rule="evenodd" />
                </svg>
                <span>Turun dari kemarin</span>
            @endif
        </div>
    @endif
</div>
