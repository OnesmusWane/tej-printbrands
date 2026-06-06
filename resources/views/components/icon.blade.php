@props(['name', 'class' => 'w-5 h-5'])

@php
    $attrs = 'class="'.$class.'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
@endphp

@switch($name)
    @case('menu') <svg {!! $attrs !!}><path d="M4 6h16M4 12h16M4 18h16"/></svg> @break
    @case('x') <svg {!! $attrs !!}><path d="M18 6 6 18M6 6l12 12"/></svg> @break
    @case('palette') <svg {!! $attrs !!}><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 22a10 10 0 1 1 10-10 3.5 3.5 0 0 1-3.5 3.5h-1.7a2 2 0 0 0-1.4 3.4l.3.3A1.6 1.6 0 0 1 14.6 22z"/></svg> @break
    @case('printer') <svg {!! $attrs !!}><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/><path d="M18 13h.01"/></svg> @break
    @case('signpost') <svg {!! $attrs !!}><path d="M12 3v18"/><path d="M18 8H8l-4 4 4 4h10z"/></svg> @break
    @case('gift') <svg {!! $attrs !!}><path d="M20 12v10H4V12"/><path d="M2 7h20v5H2z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 1 1 2.2-3.7L12 7z"/><path d="M12 7h4.5a2.5 2.5 0 1 0-2.2-3.7L12 7z"/></svg> @break
    @case('check') <svg {!! $attrs !!}><path d="M20 6 9 17l-5-5"/></svg> @break
    @case('arrow-right') <svg {!! $attrs !!}><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg> @break
    @case('quote') <svg class="{{ $class }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7.2 5C4.8 6.6 3.5 8.8 3.5 11.7V19h7.1v-7.1H7.1c.1-1.7.9-3.1 2.4-4.1L7.2 5Zm10 0c-2.4 1.6-3.7 3.8-3.7 6.7V19h7.1v-7.1h-3.5c.1-1.7.9-3.1 2.4-4.1L17.2 5Z"/></svg> @break
    @case('mail') <svg {!! $attrs !!}><path d="M4 4h16v16H4z"/><path d="m22 6-10 7L2 6"/></svg> @break
    @case('phone') <svg {!! $attrs !!}><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2.1z"/></svg> @break
    @case('map-pin') <svg {!! $attrs !!}><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg> @break
    @case('send') <svg {!! $attrs !!}><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg> @break
    @case('zoom') <svg {!! $attrs !!}><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M11 8v6M8 11h6"/></svg> @break
    @case('calendar') <svg {!! $attrs !!}><path d="M8 2v4M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/></svg> @break
    @case('tag') <svg {!! $attrs !!}><path d="M12.6 2H2v10.6L11.4 22 22 11.4z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/></svg> @break
    @default <svg {!! $attrs !!}><circle cx="12" cy="12" r="10"/></svg>
@endswitch
