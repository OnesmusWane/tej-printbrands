@extends('layouts.site')

@section('title', $service->title . ' | Tej Printbrands')

@section('content')
    @php
        $features    = is_array($service->features) ? $service->features : (json_decode($service->features ?? '[]', true) ?? []);
        $subServices = is_array($service->sub_services) ? $service->sub_services : (json_decode($service->sub_services ?? '[]', true) ?? []);

        $priceBadge = function(?string $type, $price): ?string {
            if (!$type || !$price) return null;
            $f = 'KES ' . number_format((float) $price);
            return $type === 'from' ? 'From ' . $f : $f;
        };
    @endphp

    {{-- Hero --}}
    <div class="relative flex min-h-[420px] items-end overflow-hidden bg-slate-950 pb-12 pt-32">
        @if ($service->image_url)
            <img src="{{ $service->image_url }}" alt="{{ $service->title }}" class="absolute inset-0 h-full w-full object-cover opacity-40">
        @endif
        <div class="absolute inset-0 bg-linear-to-t from-slate-950 via-slate-950/50 to-transparent"></div>
        <div class="relative z-10 mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <a href="{{ route('services') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-medium text-cyan-400 transition-colors hover:text-cyan-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Services
            </a>
            <h1 class="mb-4 text-4xl font-extrabold leading-tight text-white md:text-5xl lg:text-6xl">{{ $service->title }}</h1>
            <p class="max-w-2xl text-xl text-slate-300">{{ Str::limit($service->description, 120) }}</p>
        </div>
    </div>

    {{-- Body --}}
    <div class="bg-light py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-3">

                {{-- Main content --}}
                <div class="space-y-14 lg:col-span-2">
                    <section>
                        <h2 class="mb-5 text-3xl font-extrabold text-slate-900">Overview</h2>
                        <p class="text-lg leading-relaxed text-slate-600">{{ $service->description }}</p>
                    </section>

                    @if (!empty($subServices))
                    <section id="sub-services-section">
                        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <h2 class="text-3xl font-extrabold text-slate-900">What's Included</h2>
                            <div class="relative w-full max-w-xs">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <input
                                    id="sub-search"
                                    type="text"
                                    placeholder="Search services…"
                                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-4 text-sm text-slate-700 shadow-sm outline-none transition focus:border-cyan focus:ring-2 focus:ring-cyan/20"
                                >
                            </div>
                        </div>

                        <div id="sub-grid" class="grid gap-6 sm:grid-cols-2">
                            @foreach ($subServices as $sub)
                                @php
                                    $subTitle    = is_string($sub) ? $sub : ($sub['title'] ?? '');
                                    $subDesc     = is_array($sub) ? ($sub['description'] ?? null) : null;
                                    // Collect all images; fall back to legacy image_url
                                    $subImages   = is_array($sub) && !empty($sub['images'])
                                        ? array_values(array_filter($sub['images'], fn($u) => is_string($u) && $u !== ''))
                                        : (is_array($sub) && !empty($sub['image_url']) ? [$sub['image_url']] : []);
                                    $subPriceStr = is_array($sub) ? $priceBadge($sub['price_type'] ?? null, $sub['price'] ?? null) : null;
                                    $nestedSubs  = is_array($sub) ? array_filter($sub['sub_services'] ?? [], fn($n) => !empty(is_array($n) ? $n['title'] : $n)) : [];
                                    $hasNested   = !empty($nestedSubs);
                                    $bookUrl     = route('booking', ['type' => 'quote', 'service' => $service->title, 'sub_service' => $subTitle]);
                                    // Price: if has nested subs, use lowest nested price; else use own price
                                    $nestedPrices = [];
                                    foreach ($nestedSubs as $np) {
                                        if (is_array($np) && !empty($np['price']) && !empty($np['price_type'])) {
                                            $nestedPrices[] = (float) $np['price'];
                                        }
                                    }
                                    $displayPriceStr = !empty($nestedPrices)
                                        ? 'From KES ' . number_format(min($nestedPrices))
                                        : $subPriceStr;
                                    // Build searchable terms: parent title + all nested titles
                                    $searchTerms = strtolower($subTitle);
                                    foreach ($nestedSubs as $nst) {
                                        $nstTitle = is_array($nst) ? ($nst['title'] ?? '') : (string) $nst;
                                        if ($nstTitle) $searchTerms .= ' ' . strtolower($nstTitle);
                                    }
                                @endphp

                                {{-- Sub-services WITH nested items span full width --}}
                                <article
                                    class="sub-card group flex flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition-all hover:shadow-md {{ $hasNested ? 'sm:col-span-2' : '' }}"
                                    data-sub-title="{{ strtolower($subTitle) }}"
                                    data-search-terms="{{ $searchTerms }}"
                                >
                                    @if (!empty($subImages) && !$hasNested)
                                        {{-- Multi-image gallery: main image left, up to 3 stacked on right --}}
                                        <div class="flex h-48 overflow-hidden">
                                            <div class="{{ count($subImages) > 1 ? 'w-3/5 shrink-0' : 'relative w-full' }} overflow-hidden cursor-zoom-in"
                                                 onclick="openLightbox('{{ addslashes($subImages[0]) }}', '{{ addslashes($subTitle) }}')"
                                                 title="Click to enlarge">
                                                <img src="{{ $subImages[0] }}" alt="{{ $subTitle }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                                @if (count($subImages) === 1)
                                                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 bg-black/30 transition-opacity pointer-events-none">
                                                        <svg class="w-8 h-8 text-white drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                                    </div>
                                                @endif
                                            </div>
                                            @if (count($subImages) > 1)
                                                <div class="flex flex-1 flex-col gap-0.5 ml-0.5 overflow-hidden">
                                                    @foreach (array_slice($subImages, 1, 3) as $si => $imgUrl)
                                                        <div class="relative flex-1 overflow-hidden cursor-zoom-in"
                                                             onclick="openLightbox('{{ addslashes($imgUrl) }}', '{{ addslashes($subTitle) }}')"
                                                             title="Click to enlarge">
                                                            <img src="{{ $imgUrl }}" alt="{{ $subTitle }}" class="h-full w-full object-cover hover:scale-105 transition-transform duration-300">
                                                            @if ($si === 2 && count($subImages) > 4)
                                                                <div class="absolute inset-0 bg-black/60 flex items-center justify-center pointer-events-none">
                                                                    <span class="text-white font-bold text-lg">+{{ count($subImages) - 4 }}</span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="flex flex-1 flex-col p-6">
                                        @if ($hasNested)
                                            {{-- Parent header: title + price + desc, then image thumbnails --}}
                                            <div class="mb-5">
                                                <div class="flex items-start justify-between gap-2 flex-wrap mb-1">
                                                    <h3 class="text-xl font-extrabold text-slate-900">{{ $subTitle }}</h3>
                                                    @if ($displayPriceStr)
                                                        <span class="shrink-0 rounded-full bg-cyan-50 px-3 py-0.5 text-xs font-bold text-cyan-700 border border-cyan-200 whitespace-nowrap">{{ $displayPriceStr }}</span>
                                                    @endif
                                                </div>
                                                @if ($subDesc)
                                                    <p class="text-sm leading-relaxed text-slate-500 mb-3">{{ $subDesc }}</p>
                                                @endif
                                                @if (!empty($subImages))
                                                    <div class="flex gap-2 overflow-x-auto pb-1">
                                                        @foreach ($subImages as $imgUrl)
                                                            <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl cursor-zoom-in border border-slate-100 shadow-sm"
                                                                 onclick="openLightbox('{{ addslashes($imgUrl) }}', '{{ addslashes($subTitle) }}')"
                                                                 title="Click to enlarge">
                                                                <img src="{{ $imgUrl }}" alt="{{ $subTitle }}" class="h-full w-full object-cover hover:scale-110 transition-transform duration-200">
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Nested services as mini-cards --}}
                                            <div class="border-t border-slate-100 pt-4">
                                                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Choose an option</p>
                                                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                                    @foreach ($nestedSubs as $ns)
                                                        @php
                                                            $nsTitle    = is_array($ns) ? ($ns['title'] ?? '') : (string) $ns;
                                                            $nsDesc     = is_array($ns) ? ($ns['description'] ?? null) : null;
                                                            $nsPriceStr = is_array($ns) ? $priceBadge($ns['price_type'] ?? null, $ns['price'] ?? null) : null;
                                                            $nsBookUrl  = route('booking', ['type' => 'quote', 'service' => $service->title, 'sub_service' => $nsTitle]);
                                                            $nsImages   = is_array($ns) && !empty($ns['images'])
                                                                ? array_values(array_filter($ns['images'], fn($u) => is_string($u) && $u !== ''))
                                                                : (is_array($ns) && !empty($ns['image_url']) ? [$ns['image_url']] : []);
                                                        @endphp
                                                        <div class="flex flex-col overflow-hidden rounded-xl border border-slate-100 bg-slate-50 shadow-sm transition hover:shadow-md hover:-translate-y-0.5">
                                                            @if (!empty($nsImages))
                                                                {{-- Split gallery at h-36 --}}
                                                                <div class="flex h-36 overflow-hidden">
                                                                    <div class="{{ count($nsImages) > 1 ? 'w-3/5 shrink-0' : 'w-full' }} overflow-hidden cursor-zoom-in"
                                                                         onclick="openLightbox('{{ addslashes($nsImages[0]) }}', '{{ addslashes($nsTitle) }}')"
                                                                         title="Click to enlarge">
                                                                        <img src="{{ $nsImages[0] }}" alt="{{ $nsTitle }}" class="h-full w-full object-cover hover:scale-105 transition-transform duration-300">
                                                                    </div>
                                                                    @if (count($nsImages) > 1)
                                                                        <div class="flex flex-1 flex-col gap-0.5 ml-0.5 overflow-hidden">
                                                                            @foreach (array_slice($nsImages, 1, 3) as $nsi => $nsImgUrl)
                                                                                <div class="relative flex-1 overflow-hidden cursor-zoom-in"
                                                                                     onclick="openLightbox('{{ addslashes($nsImgUrl) }}', '{{ addslashes($nsTitle) }}')"
                                                                                     title="Click to enlarge">
                                                                                    <img src="{{ $nsImgUrl }}" alt="{{ $nsTitle }}" class="h-full w-full object-cover hover:scale-105 transition-transform duration-300">
                                                                                    @if ($nsi === 2 && count($nsImages) > 4)
                                                                                        <div class="absolute inset-0 bg-black/60 flex items-center justify-center pointer-events-none">
                                                                                            <span class="text-white font-bold text-sm">+{{ count($nsImages) - 4 }}</span>
                                                                                        </div>
                                                                                    @endif
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                            <div class="flex flex-1 flex-col p-4">
                                                                <div class="flex items-start justify-between gap-2 mb-1">
                                                                    <h4 class="text-sm font-bold text-slate-800 leading-snug">{{ $nsTitle }}</h4>
                                                                    @if ($nsPriceStr)
                                                                        <span class="shrink-0 rounded-full bg-cyan-50 px-2 py-0.5 text-[10px] font-bold text-cyan-700 border border-cyan-200 whitespace-nowrap">{{ $nsPriceStr }}</span>
                                                                    @endif
                                                                </div>
                                                                @if ($nsDesc)
                                                                    <p class="mb-3 flex-1 text-xs leading-relaxed text-slate-500">{{ $nsDesc }}</p>
                                                                @else
                                                                    <div class="flex-1 mb-3"></div>
                                                                @endif
                                                                <a href="{{ $nsBookUrl }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-cyan px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-cyan-600">
                                                                    Book this Service
                                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                        @else
                                            {{-- Simple sub-service (no nested) --}}
                                            <div class="flex items-start justify-between gap-2 mb-1">
                                                <h3 class="text-lg font-extrabold text-slate-900">{{ $subTitle }}</h3>
                                                @if ($subPriceStr)
                                                    <span class="shrink-0 rounded-full bg-cyan-50 px-3 py-0.5 text-xs font-bold text-cyan-700 border border-cyan-200 whitespace-nowrap">{{ $subPriceStr }}</span>
                                                @endif
                                            </div>
                                            @if ($subDesc)
                                                <p class="mb-4 flex-1 text-sm leading-relaxed text-slate-600">{{ $subDesc }}</p>
                                            @else
                                                <div class="flex-1"></div>
                                            @endif
                                            <a href="{{ $bookUrl }}" class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-cyan px-4 py-2.5 text-sm font-bold text-white shadow-sm shadow-cyan/20 transition hover:-translate-y-0.5 hover:bg-cyan-600">
                                                Book this Service
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                            </a>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <p id="sub-empty" class="hidden py-10 text-center text-sm text-slate-400">No services match your search.</p>
                    </section>
                    @endif
                </div>

                {{-- Sidebar --}}
                <div class="lg:col-span-1">
                    <div class="sticky top-32 rounded-2xl border border-slate-100 bg-white p-8 shadow-sm">
                        @if (!empty($features))
                            <h3 class="mb-6 text-xl font-extrabold text-slate-900">Key Benefits</h3>
                            <ul class="mb-8 space-y-4">
                                @foreach ($features as $benefit)
                                    <li class="flex items-start gap-3">
                                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="text-sm text-slate-700">{{ $benefit }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <div class="{{ !empty($features) ? 'border-t border-slate-100 pt-6' : '' }}">
                            <h4 class="mb-1 font-extrabold text-slate-900">Ready to start?</h4>
                            <p class="mb-5 text-sm text-slate-500">Get a custom quote for your specific needs.</p>
                            <a href="{{ route('booking', ['type' => 'quote', 'service' => $service->title]) }}" class="flex w-full items-center justify-center gap-2 rounded-xl bg-cyan px-6 py-3.5 text-sm font-extrabold text-white shadow-lg shadow-cyan/20 transition hover:-translate-y-0.5 hover:bg-cyan-600">
                                Request a Quote
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    (function () {
        /* ── Search ── */
        var input = document.getElementById('sub-search');
        var empty = document.getElementById('sub-empty');
        if (input) {
            input.addEventListener('input', function () {
                var q = input.value.trim().toLowerCase();
                var cards = document.querySelectorAll('.sub-card');
                var visible = 0;
                cards.forEach(function (card) {
                    // data-search-terms contains parent title + all nested titles space-separated
                    var terms = card.getAttribute('data-search-terms') || card.getAttribute('data-sub-title') || '';
                    var show = !q || terms.includes(q);
                    card.classList.toggle('hidden', !show);
                    if (show) visible++;
                });
                if (empty) empty.classList.toggle('hidden', visible > 0);
            });
        }

        /* ── Lightbox ── */
        window.openLightbox = function (src, alt) {
            var overlay = document.createElement('div');
            overlay.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.92);display:flex;align-items:center;justify-content:center;cursor:zoom-out;padding:1rem;';
            var img = document.createElement('img');
            img.src = src;
            img.alt = alt || '';
            img.style.cssText = 'max-width:100%;max-height:90vh;border-radius:12px;object-fit:contain;box-shadow:0 25px 60px rgba(0,0,0,0.5);';
            var close = document.createElement('button');
            close.innerHTML = '&times;';
            close.style.cssText = 'position:absolute;top:1rem;right:1.25rem;color:#fff;font-size:2rem;line-height:1;background:none;border:none;cursor:pointer;opacity:0.8;';
            close.addEventListener('click', function (e) { e.stopPropagation(); document.body.removeChild(overlay); document.body.style.overflow = ''; });
            overlay.appendChild(img);
            overlay.appendChild(close);
            overlay.addEventListener('click', function () { document.body.removeChild(overlay); document.body.style.overflow = ''; });
            img.addEventListener('click', function (e) { e.stopPropagation(); });
            document.body.style.overflow = 'hidden';
            document.body.appendChild(overlay);
            function onKey(e) { if (e.key === 'Escape' && document.body.contains(overlay)) { document.body.removeChild(overlay); document.body.style.overflow = ''; document.removeEventListener('keydown', onKey); } }
            document.addEventListener('keydown', onKey);
        };
    })();
    </script>
@endsection
