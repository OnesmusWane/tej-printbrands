<section id="services" class="bg-light py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @include('partials.section-heading', ['kicker' => '', 'title' => $sectionData?->heading ?? 'Our Services', 'description' => $sectionData?->subtext ?? 'From concept to completion, we deliver excellence across all our creative services.'])
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
            @foreach ($services as $service)
                @php
                    $subs = is_array($service['sub_services'] ?? null) ? $service['sub_services'] : [];
                    $allPrices = [];
                    foreach ($subs as $sub) {
                        if (is_array($sub) && !empty($sub['price']) && !empty($sub['price_type'])) {
                            $allPrices[] = (float) $sub['price'];
                        }
                        foreach ($sub['sub_services'] ?? [] as $ns) {
                            if (is_array($ns) && !empty($ns['price']) && !empty($ns['price_type'])) {
                                $allPrices[] = (float) $ns['price'];
                            }
                        }
                    }
                    $lowestPrice = !empty($allPrices) ? min($allPrices) : null;
                    $priceLabel  = $lowestPrice ? 'From KES ' . number_format($lowestPrice) : ($service['starting_price'] ?? null);
                @endphp
                <a href="{{ route('service.detail', $service['slug']) }}"
                   class="group flex flex-col overflow-hidden rounded-xl bg-white shadow-md transition duration-300 hover:-translate-y-2 hover:shadow-xl no-underline">
                    <div class="relative h-48 overflow-hidden">
                        <img src="{{ $service['image'] }}" alt="{{ $service['title'] }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-linear-to-t from-black/60 to-transparent"></div>
                        <div class="absolute -bottom-6 right-6 z-10 flex h-12 w-12 items-center justify-center rounded-full border-4 border-white bg-primary text-white shadow-lg transition group-hover:bg-dark-cyan">
                            @include('components.icon', ['name' => $service['icon'], 'class' => 'w-5 h-5'])
                        </div>
                    </div>
                    <div class="flex flex-1 flex-col p-6 pt-8">
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <h3 class="text-xl font-extrabold text-slate-900 transition group-hover:text-primary">{{ $service['title'] }}</h3>
                            @if ($priceLabel)
                                <span class="shrink-0 rounded-full bg-cyan-50 px-3 py-1 text-xs font-bold text-cyan-700 border border-cyan-200 whitespace-nowrap">{{ $priceLabel }}</span>
                            @endif
                        </div>
                        <p class="mb-6 flex-1 text-sm leading-relaxed text-slate-600">{{ $service['description'] }}</p>
                        <span class="inline-flex items-center justify-center gap-2 rounded-lg border-2 border-primary px-4 py-2.5 text-center text-sm font-bold text-primary transition group-hover:bg-primary group-hover:text-white">
                            View Services
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
