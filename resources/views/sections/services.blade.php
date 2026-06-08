<section id="services" class="bg-light py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @include('partials.section-heading', ['kicker' => '', 'title' => $sectionData?->heading ?? 'Our Services', 'description' => $sectionData?->subtext ?? 'From concept to completion, we deliver excellence across all our creative services.'])
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
            @foreach ($services as $service)
                <article class="group flex flex-col overflow-hidden rounded-xl bg-white shadow-md transition duration-300 hover:-translate-y-2 hover:shadow-xl">
                    <div class="relative h-48 overflow-hidden">
                        <img src="{{ $service['image'] }}" alt="{{ $service['title'] }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <div class="absolute -bottom-6 right-6 z-10 flex h-12 w-12 items-center justify-center rounded-full border-4 border-white bg-primary text-white shadow-lg transition group-hover:bg-dark-cyan">
                            @include('components.icon', ['name' => $service['icon'], 'class' => 'w-5 h-5'])
                        </div>
                    </div>
                    <div class="flex flex-1 flex-col p-6 pt-8">
                        <h3 class="mb-3 text-xl font-extrabold text-slate-900 transition group-hover:text-primary">{{ $service['title'] }}</h3>
                        <p class="mb-6 flex-1 text-sm leading-relaxed text-slate-600">{{ $service['description'] }}</p>
                        <a href="{{ route('service.detail', $service['slug']) }}" class="inline-flex items-center justify-center gap-2 rounded-lg border-2 border-primary px-4 py-2.5 text-center text-sm font-bold text-primary transition hover:bg-primary hover:text-white">
                            View Service Details
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
