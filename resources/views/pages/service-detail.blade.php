@extends('layouts.site')

@section('title', $service->title . ' | Tej Printbrands')

@section('content')
    @php
        $features    = is_array($service->features) ? $service->features : (json_decode($service->features ?? '[]', true) ?? []);
        $subServices = is_array($service->sub_services) ? $service->sub_services : (json_decode($service->sub_services ?? '[]', true) ?? []);
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
                                    $subTitle = is_string($sub) ? $sub : ($sub['title'] ?? '');
                                    $subImage = is_array($sub) ? ($sub['image_url'] ?? null) : null;
                                    $subDesc  = is_array($sub) ? ($sub['description'] ?? null) : null;
                                    $bookUrl  = route('booking', ['type' => 'quote', 'service' => $service->title, 'sub_service' => $subTitle]);
                                @endphp
                                <article
                                    class="sub-card group flex flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition-all hover:shadow-md"
                                    data-sub-title="{{ strtolower($subTitle) }}"
                                >
                                    @if ($subImage)
                                        <div class="h-48 overflow-hidden">
                                            <img src="{{ $subImage }}" alt="{{ $subTitle }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                        </div>
                                    @endif
                                    <div class="flex flex-1 flex-col p-6">
                                        <h3 class="mb-1 text-lg font-extrabold text-slate-900">{{ $subTitle }}</h3>
                                        @if ($subDesc)
                                            <p class="mb-4 flex-1 text-sm leading-relaxed text-slate-600">{{ $subDesc }}</p>
                                        @else
                                            <div class="flex-1"></div>
                                        @endif
                                        <a href="{{ $bookUrl }}" class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-cyan px-4 py-2.5 text-sm font-bold text-white shadow-sm shadow-cyan/20 transition hover:-translate-y-0.5 hover:bg-cyan-600">
                                            Book this Service
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                        </a>
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
        var input = document.getElementById('sub-search');
        var empty = document.getElementById('sub-empty');
        if (!input) return;
        input.addEventListener('input', function () {
            var q = input.value.trim().toLowerCase();
            var cards = document.querySelectorAll('.sub-card');
            var visible = 0;
            cards.forEach(function (card) {
                var title = card.getAttribute('data-sub-title') || '';
                var show = !q || title.includes(q);
                card.classList.toggle('hidden', !show);
                if (show) visible++;
            });
            if (empty) empty.classList.toggle('hidden', visible > 0);
        });
    })();
    </script>
@endsection
