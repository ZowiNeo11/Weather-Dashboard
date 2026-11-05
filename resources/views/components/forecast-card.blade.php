{{--
    Komponen: Forecast Card
    Menampilkan prakiraan cuaca untuk satu hari.

    Props:
    - $day    : string — nama hari (contoh: "Senin")
    - $date   : string — tanggal (contoh: "16 Jul")
    - $icon   : string — nama ikon cuaca
    - $high   : string — suhu tertinggi (contoh: "32")
    - $low    : string — suhu terendah (contoh: "24")
    - $desc   : string — deskripsi singkat (contoh: "Berawan")
    - $active : bool   — hari ini (highlighted)
--}}
@props([
    'day'    => 'Hari',
    'date'   => '',
    'icon'   => 'sun',
    'high'   => '—',
    'low'    => '—',
    'desc'   => '',
    'active' => false,
])

<div class="flex flex-col items-center gap-2 p-3 rounded-xl transition-all
            {{ $active 
               ? 'bg-blue-600 text-white shadow-md' 
               : 'bg-white border border-slate-200 text-slate-700 hover:border-blue-200 hover:bg-blue-50' }}">

    {{-- Nama hari --}}
    <span class="text-xs font-semibold uppercase tracking-wide
                 {{ $active ? 'text-blue-100' : 'text-slate-500' }}">
        {{ $day }}
    </span>

    {{-- Tanggal --}}
    @if($date)
        <span class="text-xs {{ $active ? 'text-blue-200' : 'text-slate-400' }}">{{ $date }}</span>
    @endif

    {{-- Ikon cuaca --}}
    <x-weather-icon
        :name="$icon"
        class="w-7 h-7 {{ $active ? 'text-yellow-300' : 'text-amber-400' }}"
    />

    {{-- Suhu tertinggi --}}
    <span class="text-lg font-bold leading-none">{{ $high }}°</span>

    {{-- Suhu terendah --}}
    <span class="text-xs {{ $active ? 'text-blue-200' : 'text-slate-400' }}">{{ $low }}°</span>

    {{-- Deskripsi --}}
    @if($desc)
        <span class="text-xs text-center leading-tight
                     {{ $active ? 'text-blue-100' : 'text-slate-500' }}">
            {{ $desc }}
        </span>
    @endif
</div>
