@extends('layouts.site')

@section('title', 'Services | Tej Printbrands')

@section('content')
    @php
        $servicesPage = $pagesBySlug['services'] ?? null;
        $heroSec      = $servicesPage?->sections->firstWhere('key', 'hero');
        $processSec   = $servicesPage?->sections->firstWhere('key', 'process');
        $pricingSec   = $servicesPage?->sections->firstWhere('key', 'pricing');
        $ctaLabel     = $heroSec?->settings['cta'] ?? 'Contact Us Today';
    @endphp
    <section class="relative overflow-hidden bg-gradient-to-br from-cyan-900 via-slate-800 to-slate-900 pt-36 pb-24 text-center">
        <div class="absolute inset-0 opacity-10 [background-image:linear-gradient(rgba(255,255,255,.8)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.8)_1px,transparent_1px)] [background-size:40px_40px]"></div>
        <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @php
                $headingWords = explode(' ', $heroSec?->heading ?? 'Our Premium Services');
                $half = (int) ceil(count($headingWords) / 2);
                $headingFirst = implode(' ', array_slice($headingWords, 0, $half));
                $headingSecond = implode(' ', array_slice($headingWords, $half));
            @endphp
            <h1 class="mb-6 text-4xl font-extrabold text-white md:text-5xl lg:text-6xl">{{ $headingFirst }} <span class="text-cyan-400">{{ $headingSecond }}</span></h1>
            <p class="mx-auto max-w-2xl text-xl text-cyan-50">{{ $heroSec?->subtext ?? 'Comprehensive design, printing, and branding solutions tailored to elevate your business.' }}</p>
        </div>
    </section>

    <section class="bg-white py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-24">
            @forelse ($detailedServices as $i => $service)
                @php
                    $flip     = $i % 2 !== 0;
                    $features = is_array($service->features) ? $service->features : (json_decode($service->features ?? '[]', true) ?? []);
                @endphp
                <div class="grid items-center gap-12 lg:grid-cols-2">
                    {{-- Image --}}
                    <div class="{{ $flip ? 'lg:order-2' : '' }} overflow-hidden rounded-2xl shadow-xl aspect-4/3 bg-slate-100">
                        @if ($service->image_url)
                            <img src="{{ $service->image_url }}" alt="{{ $service->title }}" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center bg-linear-to-br from-cyan-50 to-slate-100">
                                <svg class="w-16 h-16 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="{{ $flip ? 'lg:order-1' : '' }}">
                        <h2 class="mb-4 text-3xl font-extrabold text-slate-900 lg:text-4xl">{{ $service->title }}</h2>
                        <p class="mb-6 text-lg leading-relaxed text-slate-600">{{ $service->description }}</p>
                        @if (!empty($features))
                            <ul class="mb-8 space-y-3">
                                @foreach ($features as $feature)
                                    <li class="flex items-center gap-3 text-slate-700">
                                        <svg class="h-5 w-5 shrink-0 text-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="font-medium">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <a href="{{ route('service.detail', $service->slug) }}" class="inline-flex items-center gap-2 text-sm font-bold text-cyan hover:text-cyan-700 transition-colors">
                            View Services
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                    </div>
                </div>
            @empty
                <p class="text-center text-slate-500">No services are currently listed.</p>
            @endforelse
        </div>
    </section>

    <section class="bg-light py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @php
                $processWords  = explode(' ', $processSec?->heading ?? 'Our Process');
                $processHalf   = (int) ceil(count($processWords) / 2);
                $processFirst  = implode(' ', array_slice($processWords, 0, $processHalf));
                $processSecond = implode(' ', array_slice($processWords, $processHalf));
            @endphp
            <div class="mb-16 text-center">
                <h2 class="relative mb-4 inline-block text-3xl font-extrabold text-slate-900 md:text-4xl">
                    {{ $processFirst }} <span class="text-primary">{{ $processSecond }}</span>
                    <span class="absolute -bottom-3 left-1/4 right-1/4 h-1 rounded-full bg-primary"></span>
                </h2>
            </div>
            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                @foreach ($processSteps as $step)
                    <article class="relative rounded-2xl border border-slate-100 bg-white p-8 text-center shadow-sm transition hover:shadow-md">
                        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-cyan-50 text-2xl font-extrabold text-primary">{{ $step->number }}</div>
                        <h3 class="mb-3 text-xl font-extrabold text-slate-900">{{ $step->title }}</h3>
                        <p class="text-sm leading-relaxed text-slate-600">{{ $step->description }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @php
                $pricingWords  = explode(' ', $pricingSec?->heading ?? 'Pricing Packages');
                $pricingHalf   = (int) ceil(count($pricingWords) / 2);
                $pricingFirst  = implode(' ', array_slice($pricingWords, 0, $pricingHalf));
                $pricingSecond = implode(' ', array_slice($pricingWords, $pricingHalf));
                $pricingDesc   = $pricingSec?->subtext ?? 'Transparent pricing tailored to businesses of all sizes.';
            @endphp
            <div class="mb-16 text-center">
                <h2 class="relative mb-4 inline-block text-3xl font-extrabold text-slate-900 md:text-4xl">
                    {{ $pricingFirst }} <span class="text-primary">{{ $pricingSecond }}</span>
                    <span class="absolute -bottom-3 left-1/4 right-1/4 h-1 rounded-full bg-primary"></span>
                </h2>
                @if (!empty($pricingDesc))
                    <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-slate-600">{{ $pricingDesc }}</p>
                @endif
            </div>
            @php
                // Always render the most-popular tier in the centre slot
                $tiersOrdered = collect($pricingTiers);
                $popAt = $tiersOrdered->search(fn($t) => $t['highlighted'] ?? false);
                if ($popAt !== false) {
                    $pop = $tiersOrdered->splice($popAt, 1)->first();
                    $tiersOrdered->splice((int) floor($tiersOrdered->count() / 2), 0, [$pop]);
                    $tiersOrdered = $tiersOrdered->values();
                }
            @endphp
            <div class="mx-auto grid max-w-5xl items-center gap-8 pt-6 md:grid-cols-3">
                @foreach ($tiersOrdered as $tier)
                    @php
                        // Extract first number from price string (handles "From KES 15,000", "KES 75,000+", etc.)
                        preg_match('/\d[\d,]*/', $tier['price'], $pm);
                        $pv = isset($pm[0]) ? (int) str_replace(',', '', $pm[0]) : 0;
                        $tierBudget = match(true) {
                            $pv >= 75000 => 'KES 75,000+',
                            $pv >= 30000 => 'KES 30,000 - 75,000',
                            $pv >= 10000 => 'KES 10,000 - 30,000',
                            $pv > 0      => 'Below KES 10,000',
                            default      => '',
                        };
                    @endphp
                    <article class="relative rounded-2xl p-8 transition-transform {{ $tier['highlighted'] ? 'scale-105 -translate-y-4 border-t-4 border-primary bg-slate-900 text-white shadow-2xl' : 'border border-slate-100 bg-white text-slate-900 shadow-lg' }}">
                        @if ($tier['highlighted'])
                            <div class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary px-3 py-1 text-xs font-extrabold uppercase tracking-wider text-white">Most Popular</div>
                        @endif
                        <h3 class="mb-2 text-2xl font-extrabold">{{ $tier['name'] }}</h3>
                        <div class="mb-4 text-3xl font-extrabold text-primary">{{ $tier['price'] }}</div>
                        <p class="mb-8 text-sm {{ $tier['highlighted'] ? 'text-slate-300' : 'text-slate-600' }}">{{ $tier['desc'] }}</p>
                        <ul class="mb-8 space-y-4">
                            @foreach ($tier['features'] as $feature)
                                <li class="flex items-start gap-3 text-sm">@include('components.icon', ['name' => 'check', 'class' => 'w-5 h-5 text-primary shrink-0']) <span class="{{ $tier['highlighted'] ? 'text-slate-200' : 'text-slate-700' }}">{{ $feature }}</span></li>
                            @endforeach
                        </ul>
                        <a href="{{ route('booking', array_filter(['type' => 'booking', 'package' => $tier['name'], 'budget' => $tierBudget])) }}" class="block rounded-lg py-3 text-center font-extrabold transition {{ $tier['highlighted'] ? 'bg-primary text-white hover:bg-dark-cyan' : 'bg-slate-100 text-slate-900 hover:bg-slate-200' }}">Get Started</a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-primary py-20 text-center">
        <div class="mx-auto max-w-4xl px-4">
            <h2 class="mb-6 text-3xl font-extrabold text-white md:text-4xl">Ready to start your project?</h2>
            <p class="mb-8 text-lg text-cyan-50">Let's discuss how we can bring your vision to life with our premium services.</p>
            <a href="{{ route('booking', ['type' => 'booking']) }}" class="inline-block rounded-lg bg-white px-8 py-4 text-lg font-extrabold text-cyan shadow-lg transition hover:-translate-y-1 hover:bg-slate-50">{{ $ctaLabel }}</a>
        </div>
    </section>

    <script>
    (function () {
        var panels  = document.querySelectorAll('[data-service-panel]');
        var tabBtns = document.querySelectorAll('[data-tab-btn]');
        var search  = document.getElementById('sub-service-search');

        var activeId = null;

        function activateTab(id) {
            activeId = id;

            // Update tab button styles
            tabBtns.forEach(function (btn) {
                var isActive = btn.getAttribute('data-tab-btn') === String(id);
                btn.classList.toggle('text-cyan-500', isActive);
                btn.classList.toggle('border-b-2', isActive);
                btn.classList.toggle('border-cyan-500', isActive);
                btn.classList.toggle('text-slate-600', !isActive);
            });

            // Show/hide panels
            panels.forEach(function (panel) {
                panel.classList.toggle('hidden', panel.getAttribute('data-service-panel') !== String(id));
            });

            // Reset search
            if (search) search.value = '';
            filterCards('');
        }

        function filterCards(query) {
            var activePanel = document.querySelector('[data-service-panel="' + activeId + '"]');
            if (!activePanel) return;

            var cards   = activePanel.querySelectorAll('.sub-service-card');
            var empty   = activePanel.querySelector('.sub-service-empty');
            var visible = 0;

            cards.forEach(function (card) {
                var title   = card.getAttribute('data-title') || '';
                var matches = !query || title.includes(query.toLowerCase());
                card.classList.toggle('hidden', !matches);
                if (matches) visible++;
            });

            if (empty) empty.classList.toggle('hidden', visible > 0 || cards.length === 0);
        }

        // Bind tab clicks
        tabBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                activateTab(btn.getAttribute('data-tab-btn'));
            });
        });

        // Bind search
        if (search) {
            search.addEventListener('input', function () {
                filterCards(search.value.trim());
            });
        }

        // Activate first tab on load
        if (tabBtns.length > 0) {
            activateTab(tabBtns[0].getAttribute('data-tab-btn'));
        }
    })();
    </script>
@endsection
