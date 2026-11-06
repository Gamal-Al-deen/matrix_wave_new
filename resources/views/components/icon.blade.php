@props([
    'name' => 'circle',
    'class' => 'h-5 w-5',
])

<svg
    {{ $attributes->merge([
        'class' => $class,
        'viewBox' => '0 0 24 24',
        'fill' => 'none',
        'stroke' => 'currentColor',
        'stroke-width' => '1.8',
        'stroke-linecap' => 'round',
        'stroke-linejoin' => 'round',
        'aria-hidden' => 'true',
        'focusable' => 'false',
    ]) }}
>
    @switch ($name)
        @case ('home')
            <path d="M3 10.7 12 3l9 7.7"></path>
            <path d="M5.5 9.5V21h13V9.5"></path>
            <path d="M9.5 21v-6.5h5V21"></path>
            @break

        @case ('box')
            <path d="m4 7.5 8-4.5 8 4.5v9L12 21l-8-4.5z"></path>
            <path d="m4 7.5 8 4.5 8-4.5M12 12v9"></path>
            @break

        @case ('orders')
        @case ('receipt')
            <path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"></path>
            <path d="M9 8h6M9 12h6M9 16h4"></path>
            @break

        @case ('user')
            <circle cx="12" cy="8" r="4"></circle>
            <path d="M4.5 21a7.5 7.5 0 0 1 15 0"></path>
            @break

        @case ('users')
            <circle cx="9" cy="8" r="3.2"></circle>
            <path d="M3 20a6 6 0 0 1 12 0"></path>
            <path d="M16 5.5a3.2 3.2 0 0 1 0 6.2M18 14.5a5 5 0 0 1 3 4.5"></path>
            @break

        @case ('cart')
            <path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 2-1.6L21 8H6"></path>
            <circle cx="10" cy="20" r="1"></circle>
            <circle cx="18" cy="20" r="1"></circle>
            @break

        @case ('search')
            <circle cx="11" cy="11" r="7"></circle>
            <path d="m20 20-3.5-3.5"></path>
            @break

        @case ('filter')
            <path d="M4 6h16M7 12h10M10 18h4"></path>
            @break

        @case ('menu')
            <path d="M4 6h16M4 12h16M4 18h16"></path>
            @break

        @case ('close')
            <path d="M6 6l12 12M18 6 6 18"></path>
            @break

        @case ('plus')
            <path d="M12 5v14M5 12h14"></path>
            @break

        @case ('pencil')
            <path d="M4 20h4l10.5-10.5a2.8 2.8 0 0 0-4-4L4 16z"></path>
            <path d="m13.5 6.5 4 4"></path>
            @break

        @case ('trash')
            <path d="M4 7h16M9 7V4h6v3M6 7l1 14h10l1-14"></path>
            <path d="M10 11v6M14 11v6"></path>
            @break

        @case ('save')
            <path d="M5 3h11l3 3v15H5z"></path>
            <path d="M8 3v6h7V3M8 21v-7h8v7"></path>
            @break

        @case ('check')
            <path d="m5 12 4 4L19 6"></path>
            @break

        @case ('upload')
            <path d="M12 16V4M7 9l5-5 5 5"></path>
            <path d="M4 20h16"></path>
            @break

        @case ('eye')
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path>
            <circle cx="12" cy="12" r="3"></circle>
            @break

        @case ('eye-off')
            <path d="m3 3 18 18"></path>
            <path d="M10.6 5.2A10.6 10.6 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3 3.8M6.3 6.8A17 17 0 0 0 2 12s3.5 7 10 7a10.8 10.8 0 0 0 5-1.2"></path>
            <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path>
            @break

        @case ('printer')
            <path d="M6 9V3h12v6"></path>
            <rect x="3" y="9" width="18" height="8" rx="2"></rect>
            <path d="M6 14h12v7H6z"></path>
            @break

        @case ('download')
            <path d="M12 3v12"></path>
            <path d="m7 10 5 5 5-5"></path>
            <path d="M4 21h16"></path>
            @break

        @case ('settings')
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4v-.2a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.6v-.2h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"></path>
            @break

        @case ('image')
            <rect x="3" y="4" width="18" height="16" rx="2"></rect>
            <circle cx="9" cy="10" r="1.5"></circle>
            <path d="m21 15-5-5L5 20"></path>
            @break

        @case ('tags')
            <path d="M4 4h7l9 9-7 7-9-9z"></path>
            <circle cx="8" cy="8" r="1.2"></circle>
            @break

        @case ('wallet')
            <rect x="3" y="6" width="18" height="13" rx="2"></rect>
            <path d="M3 10h18M16 15h2"></path>
            @break

        @case ('share')
            <circle cx="18" cy="5" r="2.5"></circle>
            <circle cx="6" cy="12" r="2.5"></circle>
            <circle cx="18" cy="19" r="2.5"></circle>
            <path d="m8.3 10.8 7.4-4.4M8.3 13.2l7.4 4.4"></path>
            @break

        @case ('info')
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M12 10.5V17M12 7.5v.01"></path>
            @break

        @case ('phone')
            <path d="M5 4h4l2 5-2.5 1.5a14 14 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2.2 2A17 17 0 0 1 3 6.2 2 2 0 0 1 5 4Z"></path>
            @break

        @case ('mail')
            <rect x="3" y="5" width="18" height="14" rx="2"></rect>
            <path d="m3.5 6.5 8.5 6 8.5-6"></path>
            @break

        @case ('map-pin')
            <path d="M20 10c0 5.5-8 11-8 11S4 15.5 4 10a8 8 0 1 1 16 0Z"></path>
            <circle cx="12" cy="10" r="2.5"></circle>
            @break

        @case ('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2"></rect>
            <path d="M7 3v4M17 3v4M3 10h18"></path>
            @break

        @case ('logout')
            <path d="M10 4H5v16h5"></path>
            <path d="M14 8l4 4-4 4M9 12h9"></path>
            @break

        @case ('login')
            <path d="M14 4h5v16h-5"></path>
            <path d="M10 8l4 4-4 4M15 12H6"></path>
            @break

        @case ('dashboard')
            <rect x="3" y="3" width="7" height="8" rx="1.5"></rect>
            <rect x="14" y="3" width="7" height="5" rx="1.5"></rect>
            <rect x="3" y="15" width="7" height="6" rx="1.5"></rect>
            <rect x="14" y="12" width="7" height="9" rx="1.5"></rect>
            @break

        @case ('chart')
            <path d="M4 20V10M10 20V4M16 20v-7M22 20H2"></path>
            @break

        @case ('credit-card')
            <rect x="2.5" y="5" width="19" height="14" rx="2.5"></rect>
            <path d="M2.5 10h19M6 15h4"></path>
            @break

        @case ('clock')
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M12 7v5l3.5 2"></path>
            @break

        @case ('bell')
            <path d="M18 9a6 6 0 1 0-12 0c0 5-2 6.5-2 6.5h16S18 14 18 9"></path>
            <path d="M10 20a2.4 2.4 0 0 0 4 0"></path>
            @break

        @case ('warning')
            <path d="M12 3 2.8 20h18.4z"></path>
            <path d="M12 9v5M12 17.5v.01"></path>
            @break

        @case ('refresh')
            <path d="M20 7v5h-5M4 17v-5h5"></path>
            <path d="M6.1 8a7 7 0 0 1 11.5-1L20 12M4 12l2.4 5a7 7 0 0 0 11.5-1"></path>
            @break

        @case ('ban')
            <circle cx="12" cy="12" r="9"></circle>
            <path d="m5.6 5.6 12.8 12.8"></path>
            @break

        @case ('arrow-left')
            <path d="M20 12H4M10 6l-6 6 6 6"></path>
            @break

        @case ('arrow-right')
            <path d="M4 12h16M14 6l6 6-6 6"></path>
            @break

        @case ('chevron-left')
            <path d="m15 6-6 6 6 6"></path>
            @break

        @case ('chevron-right')
            <path d="m9 6 6 6-6 6"></path>
            @break

        @case ('arrow-up')
            <path d="M12 20V4M6 10l6-6 6 6"></path>
            @break

        @case ('arrow-down')
            <path d="M12 4v16M6 14l6 6 6-6"></path>
            @break

        @case ('play')
            <path d="m8 5 11 7-11 7z"></path>
            @break

        @case ('pause')
            <path d="M8 5v14M16 5v14"></path>
            @break

        @case ('external')
            <path d="M14 4h6v6M20 4l-9 9"></path>
            <path d="M19 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5"></path>
            @break

        @case ('sparkles')
            <path d="m12 3 1.4 4.1L17.5 8.5l-4.1 1.4L12 14l-1.4-4.1-4.1-1.4 4.1-1.4z"></path>
            <path d="m19 14 .8 2.2L22 17l-2.2.8L19 20l-.8-2.2L16 17l2.2-.8z"></path>
            @break

        @case ('shield')
            <path d="M12 3 4.5 6v5.5c0 4.5 3.2 7.7 7.5 9.5 4.3-1.8 7.5-5 7.5-9.5V6z"></path>
            <path d="m9 12 2 2 4-4"></path>
            @break

        @default
            <circle cx="12" cy="12" r="8"></circle>
            <path d="M12 8v4l2.5 2"></path>
    @endswitch
</svg>
