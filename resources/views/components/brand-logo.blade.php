@props(['size' => 96])

{{-- Line-art horse head for the South West Equine Services wordmark. Drawn inline so it
     needs no image asset; replace with an approved brand SVG when one is supplied. --}}
<svg
    {{ $attributes->merge(['class' => 'brand-logo']) }}
    width="{{ $size }}"
    height="{{ round($size * 0.66) }}"
    viewBox="0 0 120 80"
    fill="none"
    stroke="currentColor"
    stroke-width="1.6"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>
    {{-- ears --}}
    <path d="M52 15 L55 5 L59 13" />
    <path d="M58 13 L63 4 L64 14" />
    {{-- forehead, face and muzzle --}}
    <path d="M52 15 C45 21 38 29 33 37 C30 41 29 45 31 47 C33 49 37 49 40 47" />
    {{-- jaw and throat --}}
    <path d="M40 47 C46 47 51 44 54 39 C57 45 59 53 58 63" />
    {{-- back of neck --}}
    <path d="M64 14 C74 18 81 29 83 42 C84 50 84 57 83 64" />
    {{-- mane --}}
    <path d="M62 10 C75 12 86 22 90 36" />
    <path d="M66 17 C77 22 85 32 87 45" />
    <path d="M60 7 C70 6 82 12 88 22" />
    {{-- eye and nostril --}}
    <circle cx="45" cy="27" r="1.2" fill="currentColor" stroke="none" />
    <path d="M33 43 C34 42 35 42 36 43" />
    {{-- sweeping underline --}}
    <path d="M14 70 C40 79 82 78 110 60" />
</svg>
