@extends('layouts.site')

@section('title', 'Our Work | Tej Printbrands')

@section('content')
    @php
        $workPage        = $pagesBySlug['work'] ?? null;
        $heroSec         = $workPage?->sections->firstWhere('key', 'hero');
        $caseStudiesSec  = $workPage?->sections->firstWhere('key', 'case-studies');
        $portfolioSec    = $workPage?->sections->firstWhere('key', 'portfolio');
    @endphp
    <section class="border-b border-slate-100 bg-white pt-36 pb-20 text-center">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @php
                $heroWords  = explode(' ', $heroSec?->heading ?? 'Our Best Work');
                $heroHalf   = (int) ceil(count($heroWords) / 2);
                $heroFirst  = implode(' ', array_slice($heroWords, 0, $heroHalf));
                $heroSecond = implode(' ', array_slice($heroWords, $heroHalf));
            @endphp
            <h1 class="mb-6 text-5xl font-extrabold tracking-normal text-dark md:text-6xl lg:text-7xl">{{ $heroFirst }} <span class="text-cyan">{{ $heroSecond }}</span></h1>
            <p class="mx-auto mb-16 max-w-2xl text-xl text-slate-600">{{ $heroSec?->subtext ?? 'Explore our portfolio of successful projects, from stunning brand identities to large-scale environmental graphics.' }}</p>
            <div class="mx-auto grid max-w-4xl grid-cols-2 gap-8 md:grid-cols-4">
                @foreach ($stats as $stat)
                    <div><div class="mb-2 text-4xl font-extrabold text-dark md:text-5xl">{{ $stat['value'] }}</div><div class="text-xs font-bold uppercase tracking-wider text-cyan">{{ $stat['label'] }}</div></div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-light py-24">
        <div class="mx-auto max-w-7xl space-y-20 px-4 sm:px-6 lg:px-8">
            @php
                $csWords  = explode(' ', $caseStudiesSec?->heading ?? 'Featured Case Studies');
                $csHalf   = (int) ceil(count($csWords) / 2);
                $csFirst  = implode(' ', array_slice($csWords, 0, $csHalf));
                $csSecond = implode(' ', array_slice($csWords, $csHalf));
            @endphp
            <div class="mb-16 text-center">
                <h2 class="relative mb-4 inline-block text-3xl font-extrabold text-slate-900 md:text-4xl">
                    {{ $csFirst }} <span class="text-primary">{{ $csSecond }}</span>
                    <span class="absolute -bottom-3 left-1/4 right-1/4 h-1 rounded-full bg-primary"></span>
                </h2>
            </div>
            @foreach ($caseStudies as $study)
                <article class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-xl lg:flex">
                    <div class="h-72 lg:h-auto lg:w-1/2"><img src="{{ $study['image'] }}" alt="{{ $study['title'] }}" class="h-full w-full object-cover"></div>
                    <div class="p-8 md:p-12 lg:w-1/2">
                        <span class="mb-2 block text-sm font-extrabold uppercase tracking-wider text-cyan">{{ $study['client'] }}</span>
                        <h3 class="mb-6 text-3xl font-extrabold text-slate-900">{{ $study['title'] }}</h3>
                        <div class="mb-8 space-y-6">
                            <div><h4 class="mb-2 text-lg font-extrabold text-slate-900">The Challenge</h4><p class="leading-relaxed text-slate-600">{{ $study['challenge'] }}</p></div>
                            <div><h4 class="mb-2 text-lg font-extrabold text-slate-900">The Solution</h4><p class="leading-relaxed text-slate-600">{{ $study['solution'] }}</p></div>
                            <div><h4 class="mb-2 text-lg font-extrabold text-slate-900">Key Results</h4><ul class="list-inside list-disc space-y-1 text-slate-600">@foreach ($study['results'] as $result)<li>{{ $result }}</li>@endforeach</ul></div>
                        </div>
                        <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-6 py-3 font-semibold text-white transition hover:bg-slate-800">Start a Similar Project @include('components.icon', ['name' => 'arrow-right', 'class' => 'w-4 h-4'])</a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
    @include('sections.portfolio', ['sectionData' => $portfolioSec])
    @include('sections.testimonials')
@endsection
