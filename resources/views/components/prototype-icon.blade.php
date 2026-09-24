@props(['name', 'size' => 20])

<svg
    {{ $attributes->merge(['class' => 'prototype-icon']) }}
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>
    @switch($name)
        @case('home')
            <path d="m3 11 9-8 9 8" />
            <path d="M5 10v10h14V10M9 20v-6h6v6" />
            @break
        @case('document')
            <path d="M6 3h9l4 4v14H6z" />
            <path d="M15 3v5h4M9 12h6M9 16h6" />
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M16 3v4M8 3v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01" />
            @break
        @case('truck')
            <path d="M3 6h11v10H3zM14 10h4l3 3v3h-7z" />
            <circle cx="7" cy="18" r="2" />
            <circle cx="18" cy="18" r="2" />
            @break
        @case('practice')
            <path d="M5 18c2-6 3-9 7-12 2-1 5 0 6 2-2 0-3 1-3 3l3 3v4" />
            <path d="M7 18h12M8 8l-3-2M12 6V3" />
            @break
        @case('fuel')
            <path d="M5 3h10v18H5zM7 6h6v5H7z" />
            <path d="M15 8h2l2 2v8a1 1 0 0 0 2 0v-7l-2-2" />
            @break
        @case('settings')
            <circle cx="12" cy="12" r="3" />
            <path d="M19 12a7 7 0 0 0-.1-1l2-1.5-2-3.4-2.4 1a8 8 0 0 0-1.7-1L14.5 3h-5l-.4 3.1a8 8 0 0 0-1.7 1l-2.4-1-2 3.4L5.1 11a7 7 0 0 0 0 2L3 14.5l2 3.4 2.4-1a8 8 0 0 0 1.7 1l.4 3.1h5l.4-3.1a8 8 0 0 0 1.7-1l2.4 1 2-3.4-2.1-1.5c.1-.3.1-.7.1-1Z" />
            @break
        @case('check')
            <circle cx="12" cy="12" r="9" />
            <path d="m8 12 2.5 2.5L16 9" />
            @break
        @case('chart')
            <path d="M4 20V10M10 20V4M16 20v-7M22 20V7" />
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14" />
            @break
        @case('depot')
            <path d="M3 10 12 4l9 6v10H3zM8 20v-6h8v6M6 10h12" />
            @break
        @case('horse')
            <path d="M5 19v-7l3-5 4-2 5 3 2 4-3 1-2-2-3 3v5" />
            <path d="M8 19v-4M16 19v-6M13 6l2-3" />
            @break
        @case('location')
            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z" />
            <circle cx="12" cy="10" r="2.5" />
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" />
            @break
        @case('arrow')
            <path d="m9 18 6-6-6-6" />
            @break
        @default
            <circle cx="12" cy="12" r="9" />
    @endswitch
</svg>
