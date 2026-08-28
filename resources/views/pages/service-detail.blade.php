@extends('layouts.site')

@section('title', $service->title . ' | Tej Printbrands')
@section('canonical', route('service.detail', $service->slug))
@section('meta_description', \Illuminate\Support\Str::limit($service->description, 155))
@section('meta_keywords', strtolower($service->title) . ', printing services Kenya, Tej Printbrands')

@include('partials.breadcrumb-schema', ['items' => [
    ['name' => 'Home', 'url' => route('home')],
    ['name' => 'Services', 'url' => route('services')],
    ['name' => $service->title, 'url' => route('service.detail', $service->slug)],
]])

@php
    $serviceSchema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => $service->title,
        'description' => $service->description,
        'image' => $service->image_url ?? null,
        'url' => route('service.detail', $service->slug),
        'provider' => [
            '@type' => 'Organization',
            'name' => $siteSettings['company']['company_name'] ?? 'Tej Printbrands',
        ],
    ]);
@endphp

@push('schema')
    <script type="application/ld+json">{!! json_encode($serviceSchema, JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
    @php
        $features     = is_array($service->features) ? $service->features : (json_decode($service->features ?? '[]', true) ?? []);
        $subServices  = is_array($service->sub_services) ? $service->sub_services : (json_decode($service->sub_services ?? '[]', true) ?? []);
        $serviceImages = is_array($service->images) && !empty(array_filter($service->images))
            ? array_values(array_filter($service->images, fn($u) => is_string($u) && $u !== ''))
            : ($service->image_url ? [$service->image_url] : []);

        $priceBadge = function(?string $type, $price): ?string {
            if (!$type || !$price) return null;
            $f = 'Ksh ' . number_format((float) $price);
            return $type === 'from' ? 'From ' . $f : $f;
        };
    @endphp

    {{-- Hero --}}
    <div class="relative flex min-h-[420px] items-end overflow-hidden bg-slate-950 pb-12 pt-32">
        @if ($service->image_url)
            <x-responsive-image :src="$service->image_url" :alt="$service->title" variant="hero" sizes="100vw" :eager="true" class="absolute inset-0 h-full w-full object-cover opacity-40" />
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

                    @if (!empty($serviceImages))
                    <section id="service-gallery">
                        <div class="relative aspect-4/3 overflow-hidden rounded-2xl border border-slate-100 bg-slate-100 shadow-sm sm:aspect-video">
                            @foreach ($serviceImages as $i => $img)
                                <div
                                    class="gallery-main-slide absolute inset-0 cursor-zoom-in {{ $i === 0 ? '' : 'hidden' }}"
                                    data-gallery-index="{{ $i }}"
                                    onclick="openLightbox({{ json_encode($serviceImages) }}, {{ $i }}, '{{ addslashes($service->title) }}')"
                                    title="Click to enlarge"
                                >
                                    <x-responsive-image :src="$img" :alt="$service->title" variant="hero" sizes="(min-width: 1024px) 60vw, 100vw" :eager="$i === 0" class="h-full w-full object-cover" />
                                </div>
                            @endforeach
                            @if (count($serviceImages) > 1)
                                <div class="pointer-events-none absolute bottom-3 right-3 rounded-full bg-black/50 px-2.5 py-1 text-xs font-semibold text-white">
                                    <span id="gallery-counter">1</span> / {{ count($serviceImages) }}
                                </div>
                            @endif
                        </div>
                        @if (count($serviceImages) > 1)
                            <div class="mt-3 flex gap-3 overflow-x-auto pb-1">
                                @foreach ($serviceImages as $i => $img)
                                    <button
                                        type="button"
                                        class="gallery-thumb-btn h-20 w-20 shrink-0 overflow-hidden rounded-xl border-2 transition-colors {{ $i === 0 ? 'border-cyan' : 'border-transparent' }}"
                                        data-gallery-index="{{ $i }}"
                                        onclick="showGalleryImage({{ $i }})"
                                    >
                                        <x-responsive-image :src="$img" :alt="$service->title" variant="thumb" sizes="80px" class="h-full w-full object-cover" />
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </section>
                    @endif

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
                                        ? 'From Ksh ' . number_format(min($nestedPrices))
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
                                                 onclick="openLightbox({{ json_encode($subImages) }}, 0, '{{ addslashes($subTitle) }}')"
                                                 title="Click to enlarge">
                                                <x-responsive-image :src="$subImages[0]" :alt="$subTitle" variant="card" sizes="(min-width: 640px) 40vw, 60vw" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
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
                                                             onclick="openLightbox({{ json_encode($subImages) }}, {{ $si + 1 }}, '{{ addslashes($subTitle) }}')"
                                                             title="Click to enlarge">
                                                            <x-responsive-image :src="$imgUrl" :alt="$subTitle" variant="thumb" sizes="120px" class="h-full w-full object-cover hover:scale-105 transition-transform duration-300" />
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
                                                        @foreach ($subImages as $ti => $imgUrl)
                                                            <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl cursor-zoom-in border border-slate-100 shadow-sm"
                                                                 onclick="openLightbox({{ json_encode($subImages) }}, {{ $ti }}, '{{ addslashes($subTitle) }}')"
                                                                 title="Click to enlarge">
                                                                <x-responsive-image :src="$imgUrl" :alt="$subTitle" variant="thumb" sizes="64px" class="h-full w-full object-cover hover:scale-110 transition-transform duration-200" />
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
                                                                         onclick="openLightbox({{ json_encode($nsImages) }}, 0, '{{ addslashes($nsTitle) }}')"
                                                                         title="Click to enlarge">
                                                                        <x-responsive-image :src="$nsImages[0]" :alt="$nsTitle" variant="card" sizes="(min-width: 640px) 25vw, 45vw" class="h-full w-full object-cover hover:scale-105 transition-transform duration-300" />
                                                                    </div>
                                                                    @if (count($nsImages) > 1)
                                                                        <div class="flex flex-1 flex-col gap-0.5 ml-0.5 overflow-hidden">
                                                                            @foreach (array_slice($nsImages, 1, 3) as $nsi => $nsImgUrl)
                                                                                <div class="relative flex-1 overflow-hidden cursor-zoom-in"
                                                                                     onclick="openLightbox({{ json_encode($nsImages) }}, {{ $nsi + 1 }}, '{{ addslashes($nsTitle) }}')"
                                                                                     title="Click to enlarge">
                                                                                    <x-responsive-image :src="$nsImgUrl" :alt="$nsTitle" variant="thumb" sizes="100px" class="h-full w-full object-cover hover:scale-105 transition-transform duration-300" />
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

        /* ── Main service gallery ── */
        window.showGalleryImage = function (index) {
            document.querySelectorAll('.gallery-main-slide').forEach(function (slide) {
                slide.classList.toggle('hidden', slide.getAttribute('data-gallery-index') !== String(index));
            });
            document.querySelectorAll('.gallery-thumb-btn').forEach(function (btn) {
                var active = btn.getAttribute('data-gallery-index') === String(index);
                btn.classList.toggle('border-cyan', active);
                btn.classList.toggle('border-transparent', !active);
            });
            var counter = document.getElementById('gallery-counter');
            if (counter) counter.textContent = String(index + 1);
        };

        /* ── Lightbox ── */
        window.openLightbox = function (images, startIndex, alt) {
            images = Array.isArray(images) ? images : [images];
            images = images.filter(function (u) { return typeof u === 'string' && u; });
            if (!images.length) return;
            var index = Math.max(0, Math.min(startIndex || 0, images.length - 1));

            var overlay = document.createElement('div');
            overlay.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.92);display:flex;align-items:center;justify-content:center;cursor:zoom-out;padding:1rem;';

            var img = document.createElement('img');
            img.alt = alt || '';
            img.style.cssText = 'max-width:100%;max-height:90vh;border-radius:12px;object-fit:contain;box-shadow:0 25px 60px rgba(0,0,0,0.5);';

            var close = document.createElement('button');
            close.innerHTML = '&times;';
            close.style.cssText = 'position:absolute;top:1rem;right:1.25rem;color:#fff;font-size:2rem;line-height:1;background:none;border:none;cursor:pointer;opacity:0.8;z-index:1;';

            var counter = document.createElement('div');
            counter.style.cssText = 'position:absolute;bottom:1rem;left:50%;transform:translateX(-50%);color:#fff;font-size:0.75rem;font-weight:600;background:rgba(0,0,0,0.55);padding:0.25rem 0.75rem;border-radius:999px;pointer-events:none;';

            function render() {
                img.src = images[index];
                counter.textContent = (index + 1) + ' / ' + images.length;
            }

            function go(dir) {
                index = (index + dir + images.length) % images.length;
                render();
            }

            function makeNavBtn(dir) {
                var btn = document.createElement('button');
                btn.setAttribute('aria-label', dir < 0 ? 'Previous image' : 'Next image');
                btn.innerHTML = dir < 0
                    ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>'
                    : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>';
                btn.style.cssText = 'position:absolute;top:50%;' + (dir < 0 ? 'left:0.75rem;' : 'right:0.75rem;') + 'transform:translateY(-50%);color:#fff;background:rgba(255,255,255,0.12);border:none;border-radius:9999px;width:44px;height:44px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background 0.15s;z-index:1;';
                btn.addEventListener('mouseenter', function () { btn.style.background = 'rgba(255,255,255,0.25)'; });
                btn.addEventListener('mouseleave', function () { btn.style.background = 'rgba(255,255,255,0.12)'; });
                btn.addEventListener('click', function (e) { e.stopPropagation(); go(dir); });
                return btn;
            }

            function teardown() {
                if (document.body.contains(overlay)) document.body.removeChild(overlay);
                document.body.style.overflow = '';
                document.removeEventListener('keydown', onKey);
            }

            function onKey(e) {
                if (e.key === 'Escape') { teardown(); return; }
                if (images.length > 1) {
                    if (e.key === 'ArrowLeft') go(-1);
                    if (e.key === 'ArrowRight') go(1);
                }
            }

            render();
            overlay.appendChild(img);
            overlay.appendChild(close);
            if (images.length > 1) {
                overlay.appendChild(makeNavBtn(-1));
                overlay.appendChild(makeNavBtn(1));
                overlay.appendChild(counter);
            }
            close.addEventListener('click', function (e) { e.stopPropagation(); teardown(); });
            overlay.addEventListener('click', teardown);
            img.addEventListener('click', function (e) { e.stopPropagation(); });

            document.body.style.overflow = 'hidden';
            document.body.appendChild(overlay);
            document.addEventListener('keydown', onKey);
        };
    })();
    </script>
@endsection
