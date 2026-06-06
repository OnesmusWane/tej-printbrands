@extends('layouts.site')

@section('title', 'Services | Tej Printbrands')

@section('content')
    @php
        $servicesPage = $pagesBySlug['services'] ?? null;
        $heroSec      = $servicesPage?->sections->firstWhere('key', 'hero');
        $ctaLabel     = $heroSec?->settings['cta'] ?? 'Contact Us Today';
    @endphp
    <section class="relative overflow-hidden bg-gradient-to-br from-cyan-900 via-slate-800 to-slate-900 pt-36 pb-24 text-center">
        <div class="absolute inset-0 opacity-10 [background-image:linear-gradient(rgba(255,255,255,.8)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.8)_1px,transparent_1px)] [background-size:40px_40px]"></div>
        <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h1 class="mb-6 text-4xl font-extrabold text-white md:text-5xl lg:text-6xl">{{ $heroSec?->heading ?? 'Our Premium' }} <span class="text-cyan-400">Services</span></h1>
            <p class="mx-auto max-w-2xl text-xl text-cyan-50">{{ $heroSec?->subtext ?? 'Comprehensive design, printing, and branding solutions tailored to elevate your business.' }}</p>
        </div>
    </section>

    <section class="bg-white py-24">
        <div class="mx-auto max-w-7xl space-y-24 px-4 sm:px-6 lg:px-8">
            @foreach ($detailedServices as $service)
                <div class="grid items-center gap-12 lg:grid-cols-2">
                    <div class="{{ $service['reverse'] ? 'lg:order-2' : '' }}">
                        <div class="relative aspect-[4/3] overflow-hidden rounded-2xl shadow-2xl">
                            <img src="{{ $service['image'] }}" alt="{{ $service['title'] }}" class="h-full w-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/55 to-transparent"></div>
                            <div class="absolute bottom-6 left-6 flex h-14 w-14 items-center justify-center rounded-xl bg-primary text-white shadow-lg">@include('components.icon', ['name' => $service['icon'], 'class' => 'w-7 h-7'])</div>
                        </div>
                    </div>
                    <div class="{{ $service['reverse'] ? 'lg:order-1' : '' }}">
                        <h2 class="mb-6 text-3xl font-extrabold text-slate-900 md:text-4xl">{{ $service['title'] }}</h2>
                        <p class="mb-8 text-lg leading-relaxed text-slate-600">{{ $service['description'] }}</p>
                        <ul class="mb-8 space-y-3">
                            @foreach ($service['features'] as $feature)
                                <li class="flex items-center gap-3 font-semibold text-slate-700">@include('components.icon', ['name' => 'check', 'class' => 'w-5 h-5 text-primary shrink-0']) {{ $feature }}</li>
                            @endforeach
                        </ul>
                        <a href="{{ route('booking', ['type' => 'quote', 'service' => $service['title']]) }}" class="inline-flex items-center gap-2 font-extrabold text-primary transition hover:text-dark-cyan">Inquire about this service @include('components.icon', ['name' => 'arrow-right', 'class' => 'w-5 h-5'])</a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="bg-light py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @include('partials.section-heading', ['kicker' => 'Our', 'title' => 'Process'])
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
            @include('partials.section-heading', ['kicker' => 'Pricing', 'title' => 'Packages', 'description' => 'Transparent pricing tailored to businesses of all sizes.'])
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
@endsection
