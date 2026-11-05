{{--
    Komponen: Weather Icon
    Menyediakan koleksi ikon SVG untuk kebutuhan weather dashboard.
    Menggunakan inline SVG agar tidak butuh library ikon eksternal.

    Props:
    - $name  : string — nama ikon (sun|moon|cloud|rain|snow|wind|droplet|eye|thermometer|gauge|storm|cloudy)
    - $class : string — kelas tambahan Tailwind (ukuran, warna)
--}}
@props([
    'name'  => 'sun',
    'class' => 'w-6 h-6',
])

@php
    $icons = [
        'sun' => '<circle cx="12" cy="12" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4.22 4.22l1.42 1.42m12.72 12.72 1.42 1.42M2 12h2m16 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>',

        'moon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>',

        'cloud' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.5 19H9a7 7 0 113.56-13.06A5.5 5.5 0 0117.5 19z"/>',

        'rain' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.5 16H9a7 7 0 113.56-13.06A5.5 5.5 0 0117.5 16zm-8 4v.01M12 20v.01M16 20v.01"/>',

        'snow' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 2v20M4.93 4.93l14.14 14.14M2 12h20M4.93 19.07L19.07 4.93"/>',

        'wind' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.59 4.59A2 2 0 1 1 11 8H2m10.59 11.41A2 2 0 1 0 14 16H2m15.73-8.27A2.5 2.5 0 1 1 19.5 12H2"/>',

        'droplet' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>',

        'eye' => '<path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',

        'thermometer' => '<path stroke-linecap="round" stroke-linejoin="round" d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"/>',

        'gauge' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 22C6.48 22 2 17.52 2 12S6.48 2 12 2s10 4.48 10 10"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/>',

        'storm' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 16.9A5 5 0 0 0 18 7h-1.26A8 8 0 1 0 4 15.25"/><polyline stroke-linecap="round" stroke-linejoin="round" points="13 11 9 17 15 17 11 23"/>',

        'cloudy' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.5 19H9a7 7 0 113.56-13.06A5.5 5.5 0 0117.5 19z"/><path stroke-linecap="round" stroke-linejoin="round" d="M22 19H18"/>',
    ];

    $path = $icons[$name] ?? $icons['sun'];
@endphp

{{-- Render SVG dengan path yang dipilih --}}
<svg
    xmlns="http://www.w3.org/2000/svg"
    class="{{ $class }}"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
    stroke-width="2"
    aria-hidden="true"
>
    {!! $path !!}
</svg>
