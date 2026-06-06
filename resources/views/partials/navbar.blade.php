@php
    $links = [
        ['name' => 'Home',     'route' => 'home'],
        ['name' => 'Services', 'route' => 'services'],
        ['name' => 'Work',     'route' => 'work'],
        ['name' => 'Shop',     'route' => 'products'],
        ['name' => 'Gallery',  'route' => 'gallery'],
        ['name' => 'Contact',  'route' => 'contact'],
    ];
@endphp

<header data-navbar class="fixed inset-x-0 top-0 z-50 border-b border-slate-200/70 bg-white/95 py-4 shadow-sm backdrop-blur transition-all duration-300">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        @php $co = $siteSettings['company'] ?? []; @endphp
        <a href="{{ route('home') }}" class="group flex min-w-0 items-center gap-2">
            @if (!empty($co['logo_url']))
                <img src="{{ $co['logo_url'] }}" alt="{{ $co['company_name'] ?? 'Tej Printbrands' }}" class="h-10 w-auto object-contain shrink-0">
            @else
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-cyan text-xl font-bold text-white transition-colors group-hover:bg-cyan-600">TJ</span>
                <span class="truncate text-xl font-extrabold text-dark sm:text-2xl">{{ $co['company_name'] ?? 'Tej' }} <span class="text-cyan">{{ empty($co['company_name']) ? 'Printbrands' : '' }}</span></span>
            @endif
        </a>

        <nav class="hidden items-center gap-6 md:flex lg:gap-8">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}" class="text-sm font-medium transition-colors {{ request()->routeIs($link['route']) ? 'text-cyan' : 'text-slate-600 hover:text-cyan' }}">{{ $link['name'] }}</a>
            @endforeach

            <div class="ml-2 flex items-center gap-4 border-l border-gray-200 pl-6">
                {{-- Cart bag icon --}}
                <a href="{{ route('cart') }}" class="relative p-2 text-slate-600 transition-colors hover:text-cyan" aria-label="Cart">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                        <line x1="3" y1="6" x2="21" y2="6" stroke-linecap="round"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 10a4 4 0 01-8 0"/>
                    </svg>
                    @if (($cartCount ?? 0) > 0)
                        <span class="absolute right-0 top-0 flex h-4 w-4 items-center justify-center rounded-full bg-red text-[10px] font-bold text-white">{{ $cartCount }}</span>
                    @endif
                </a>

                {{-- Auth --}}
                @auth
                    <details class="relative" data-user-menu>
                        <summary class="flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-full bg-cyan text-sm font-bold text-white transition-colors hover:bg-cyan-600">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </summary>
                        <div class="absolute right-0 mt-2 w-48 overflow-hidden rounded-xl border border-gray-100 bg-white py-2 shadow-lg">
                            <div class="mb-1 border-b border-gray-100 px-4 py-2">
                                <p class="truncate text-sm font-semibold text-dark">{{ Auth::user()->name }}</p>
                            </div>
                            <a href="{{ route('account.dashboard') }}" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                My Profile
                            </a>
                            <form action="{{ route('logout') }}" method="post">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-red hover:bg-red/5">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </details>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-600 transition-colors hover:text-cyan">Sign In</a>
                @endauth

                <a href="{{ route('booking', ['type' => 'booking']) }}" class="ml-2 rounded-lg bg-cyan px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-cyan/20 transition-all hover:-translate-y-0.5 hover:bg-cyan-600 hover:shadow-lg">Book a Service</a>
            </div>
        </nav>

        {{-- Mobile: cart + hamburger --}}
        <div class="flex items-center gap-4 md:hidden">
            <a href="{{ route('cart') }}" class="relative p-2 text-slate-600 transition-colors hover:text-cyan" aria-label="Cart">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6" stroke-linecap="round"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 10a4 4 0 01-8 0"/>
                </svg>
                @if (($cartCount ?? 0) > 0)
                    <span class="absolute right-0 top-0 flex h-4 w-4 items-center justify-center rounded-full bg-red text-[10px] font-bold text-white">{{ $cartCount }}</span>
                @endif
            </a>
            <button data-mobile-menu-button class="rounded-lg p-2 text-slate-600 transition hover:bg-slate-100 hover:text-cyan" type="button" aria-label="Toggle menu" aria-expanded="false">
                <span data-menu-open-icon>@include('components.icon', ['name' => 'menu', 'class' => 'w-6 h-6'])</span>
                <span data-menu-close-icon class="hidden">@include('components.icon', ['name' => 'x', 'class' => 'w-6 h-6'])</span>
            </button>
        </div>
    </div>

    <div data-mobile-menu class="absolute left-0 top-full hidden w-full flex-col gap-2 border-t border-gray-100 bg-white px-4 py-4 shadow-xl md:hidden">
        @auth
            <div class="mb-2 flex items-center gap-3 border-b border-gray-100 px-4 pb-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-cyan text-sm font-bold text-white">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                <div>
                    <div class="font-semibold text-dark">{{ Auth::user()->name }}</div>
                    <a href="{{ route('account.dashboard') }}" class="text-xs font-medium text-cyan">View Profile</a>
                </div>
            </div>
        @endauth
        @foreach ($links as $link)
            <a href="{{ route($link['route']) }}" class="rounded-lg px-4 py-3 text-sm font-medium transition-colors {{ request()->routeIs($link['route']) ? 'bg-cyan-50 text-cyan' : 'text-slate-800 hover:bg-slate-50' }}">{{ $link['name'] }}</a>
        @endforeach
        @auth
            <form action="{{ route('logout') }}" method="post" class="px-4">
                @csrf
                <button type="submit" class="py-2 text-left text-sm font-medium text-red">Sign Out</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="rounded-lg px-4 py-3 text-sm font-medium text-slate-800 hover:bg-slate-50">Sign In / Register</a>
        @endauth
        <div class="mt-2 px-4">
            <a href="{{ route('booking', ['type' => 'booking']) }}" class="block rounded-lg bg-cyan px-4 py-3 text-center text-sm font-semibold text-white">Book a Service</a>
        </div>
    </div>
</header>
