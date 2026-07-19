@php
    $sectionData = $sectionData ?? null;
    $categories  = collect($portfolioItems)->pluck('category')->unique()->prepend('All')->values();
    $portWords   = explode(' ', $sectionData?->heading ?? 'Our Work');
    $portHalf    = (int) ceil(count($portWords) / 2);
    $portFirst   = implode(' ', array_slice($portWords, 0, $portHalf));
    $portSecond  = implode(' ', array_slice($portWords, $portHalf));
    $portDesc    = $sectionData?->subtext ?? 'A showcase of our recent projects.';
@endphp
<section id="work" class="relative overflow-hidden bg-white py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-16 text-center">
            <h2 class="relative mb-4 inline-block text-3xl font-extrabold text-slate-900 md:text-4xl">
                {{ $portFirst }} <span class="text-primary">{{ $portSecond }}</span>
                <span class="absolute -bottom-3 left-1/4 right-1/4 h-1 rounded-full bg-primary"></span>
            </h2>
            @if (!empty($portDesc))
                <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-slate-600">{{ $portDesc }}</p>
            @endif
        </div>

        <div data-portfolio class="mt-12">
            <div class="mb-12 flex flex-wrap justify-center gap-2 md:gap-4">
                @foreach ($categories as $category)
                    <button type="button" data-filter="{{ $category }}" class="portfolio-filter relative px-4 py-2 text-sm font-semibold transition md:text-base {{ $loop->first ? 'text-primary after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:bg-primary' : 'text-slate-600 hover:text-slate-900' }}">{{ $category }}</button>
                @endforeach
            </div>

            <div class="grid gap-6 md:gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($portfolioItems as $item)
                    <article data-portfolio-card data-category="{{ $item['category'] }}" data-index="{{ $loop->index }}" class="{{ $loop->index >= 6 ? 'hidden' : '' }} group relative aspect-[4/3] cursor-pointer overflow-hidden rounded-xl bg-slate-100 shadow-md" data-project='@json($item)'>
                        <x-responsive-image :src="$item['image']" :alt="$item['title']" variant="card" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" class="h-full w-full object-cover transition duration-700 group-hover:scale-110" />
                        <div class="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-slate-950/90 via-slate-950/40 to-transparent p-6 opacity-0 transition duration-300 group-hover:opacity-100">
                            <span class="mb-2 text-sm font-bold text-cyan-300">{{ $item['category'] }}</span>
                            <h3 class="mb-4 text-xl font-extrabold text-white">{{ $item['title'] }}</h3>
                            <span class="w-max rounded-full border border-white/30 px-4 py-2 text-sm text-white transition group-hover:bg-white group-hover:text-slate-900">View</span>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-12 text-center">
                <button data-load-more type="button" class="rounded-lg bg-secondary px-8 py-3 font-bold text-white shadow-md shadow-secondary/20 transition hover:bg-red-600">Load More</button>
            </div>
        </div>
    </div>
</section>

<div data-project-modal class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
    <div data-modal-panel class="relative max-h-[91vh] w-full max-w-4xl overflow-auto rounded-2xl bg-white p-6 shadow-2xl md:p-8">
        <button data-modal-close type="button" class="absolute right-3 top-3 z-20 rounded-full bg-slate-700/80 p-2 text-white transition hover:bg-slate-900" aria-label="Close project modal">@include('components.icon', ['name' => 'x', 'class' => 'w-6 h-6'])</button>

        <div class="relative mb-8 aspect-[16/7] overflow-hidden rounded-lg bg-slate-100 shadow-md">
            <img data-modal-image src="" alt="" class="h-full w-full object-cover">
            <span data-modal-category class="absolute bottom-4 left-5 rounded-full bg-primary px-4 py-2 text-xs font-extrabold text-white shadow-lg"></span>
        </div>

        <div class="grid gap-8 lg:grid-cols-[1fr_260px]">
            <div>
                <h3 data-modal-title class="mb-3 text-3xl font-extrabold text-slate-900 md:text-4xl"></h3>
                <div class="mb-6 flex flex-wrap items-center gap-x-5 gap-y-2 border-b border-slate-200 pb-5 text-sm text-slate-600">
                    <span class="inline-flex items-center gap-2">@include('components.icon', ['name' => 'map-pin', 'class' => 'w-4 h-4 text-primary']) <span data-modal-client></span></span>
                    <span class="inline-flex items-center gap-2">@include('components.icon', ['name' => 'calendar', 'class' => 'w-4 h-4 text-primary']) <span data-modal-date></span></span>
                    <span class="inline-flex items-center gap-2">@include('components.icon', ['name' => 'tag', 'class' => 'w-4 h-4 text-primary']) <span data-modal-meta></span></span>
                </div>
                <p data-modal-description class="mb-7 leading-relaxed text-slate-600"></p>
                <h4 class="mb-4 text-lg font-extrabold text-slate-900">Project Gallery</h4>
                <div data-modal-gallery class="grid grid-cols-2 gap-4"></div>
            </div>

            <aside class="space-y-6">
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-5">
                    <h4 class="mb-4 font-extrabold text-slate-900">Services Provided</h4>
                    <div data-modal-services class="flex flex-wrap gap-2"></div>
                </div>
                <div class="rounded-lg border border-cyan-200 bg-cyan-50 p-5 text-center">
                    <h4 class="mb-3 text-lg font-extrabold text-slate-900">Like what you see?</h4>
                    <p class="mb-5 text-sm leading-relaxed text-slate-600">Let's create something amazing together for your brand.</p>
                    <div class="space-y-3">
                        <a href="{{ route('booking', ['type' => 'quote']) }}" class="block rounded-lg bg-secondary px-4 py-3 text-sm font-extrabold text-white shadow-md shadow-secondary/20 transition hover:bg-red-600">Request a Quote</a>
                        <a href="{{ route('booking', ['type' => 'booking']) }}" class="block rounded-lg border border-primary bg-white px-4 py-3 text-sm font-extrabold text-primary transition hover:bg-primary hover:text-white">Book a Service</a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
