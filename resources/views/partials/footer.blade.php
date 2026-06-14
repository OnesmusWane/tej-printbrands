@php
    $co      = $siteSettings['company']  ?? [];
    $ct      = $siteSettings['contact']  ?? [];
    $socials = $siteSettings['socials']  ?? [];
    $logoUrl     = $co['logo_url']    ?? null;
    $companyName = $co['name']        ?? 'Tej Printbrands';
    $address = $ct['address'] ?? null;
    $phone   = $ct['phone']   ?? null;
    $email   = $ct['email']   ?? null;

    $socialLinks = [
        'facebook'  => ['label' => 'Facebook',  'icon' => 'M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z'],
        'instagram' => ['label' => 'Instagram', 'icon' => 'M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37zm1.5-4.87h.01M6.5 19.5h11a2 2 0 002-2v-11a2 2 0 00-2-2h-11a2 2 0 00-2 2v11a2 2 0 002 2z'],
        'twitter'   => ['label' => 'Twitter',   'icon' => 'M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z'],
        'linkedin'  => ['label' => 'LinkedIn',  'icon' => 'M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z M4 6a2 2 0 100-4 2 2 0 000 4z'],
    ];

    $quickLinks = [
        ['name' => 'Home',     'route' => 'home'],
        ['name' => 'Services', 'route' => 'services'],
        ['name' => 'Work',     'route' => 'work'],
        ['name' => 'Gallery',  'route' => 'gallery'],
        ['name' => 'Contact',  'route' => 'contact'],
    ];

    $serviceLinks = ['Graphic Design', 'Printing', 'Branding', 'Signage', 'Promotional Items'];
@endphp

<footer class="bg-dark text-gray-600">
    <div class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 py-16 sm:px-6 md:grid-cols-2 lg:grid-cols-4 lg:px-8">
        {{-- Brand + Socials --}}
        <div>
            <a href="{{ route('home') }}" class="mb-4 inline-flex items-center gap-2">
                @if ($logoUrl)
                    <span class="flex items-center justify-center rounded-lg bg-white/10 px-3 py-1.5">
                        <img src="{{ $logoUrl }}" alt="{{ $companyName }}" class="h-8 w-auto object-contain brightness-0 invert">
                    </span>
                @else
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-cyan text-xl font-bold text-white">TJ</span>
                    <span class="text-2xl font-bold tracking-tight text-white">{{ $companyName }}</span>
                @endif
            </a>
            <p class="mb-5 max-w-xs text-sm leading-relaxed text-gray-400">Premium graphic design, printing, and branding solutions that help your business stand out.</p>
            @php $activeSocials = array_filter($socialLinks, fn($key) => !empty($socials[$key]), ARRAY_FILTER_USE_KEY); @endphp
            @if (!empty($activeSocials))
            <div class="flex items-center gap-3">
                @foreach ($activeSocials as $key => $social)
                    <a href="{{ $socials[$key] }}" aria-label="{{ $social['label'] }}" target="_blank" rel="noopener noreferrer"
                       class="flex h-9 w-9 items-center justify-center rounded-full bg-white/5 text-gray-400 transition-colors hover:bg-cyan hover:text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="{{ $social['icon'] }}"/>
                        </svg>
                    </a>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Quick Links --}}
        <div>
            <h4 class="mb-4 font-semibold text-white">Quick Links</h4>
            <ul class="space-y-2.5 text-sm">
                @foreach ($quickLinks as $link)
                    <li><a href="{{ route($link['route']) }}" class="text-gray-400 transition-colors hover:text-cyan">{{ $link['name'] }}</a></li>
                @endforeach
            </ul>
        </div>

        {{-- Services --}}
        <div>
            <h4 class="mb-4 font-semibold text-white">Services</h4>
            <ul class="space-y-2.5 text-sm">
                @foreach ($serviceLinks as $service)
                    <li><a href="{{ route('services') }}" class="text-gray-400 transition-colors hover:text-cyan">{{ $service }}</a></li>
                @endforeach
            </ul>
        </div>

        {{-- Get in Touch --}}
        @if ($address || $phone || $email)
        <div>
            <h4 class="mb-4 font-semibold text-white">Get in Touch</h4>
            <ul class="space-y-3 text-sm text-gray-400">
                @if ($address)
                <li class="flex items-start gap-3">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>{{ $address }}</span>
                </li>
                @endif
                @if ($phone)
                <li class="flex items-start gap-3">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <span>{{ $phone }}</span>
                </li>
                @endif
                @if ($email)
                <li class="flex items-start gap-3">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span>{{ $email }}</span>
                </li>
                @endif
            </ul>
        </div>
        @endif
    </div>

    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-5 sm:flex-row sm:px-6 lg:px-8">
            <p class="text-sm text-gray-500">&copy; {{ date('Y') }} Tej Printbrands. All rights reserved.</p>
            <div class="flex items-center gap-5 text-sm">
                <a href="#" class="text-gray-500 transition-colors hover:text-cyan">Privacy</a>
                <a href="#" class="text-gray-500 transition-colors hover:text-cyan">Terms</a>
            </div>
        </div>
    </div>
</footer>
