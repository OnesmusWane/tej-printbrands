<section class="border-b border-slate-100 bg-white py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 class="mb-12 text-center text-2xl font-extrabold text-dark md:text-3xl">{{ $sectionData?->heading ?? "Brands We've Worked With" }}</h2>
        <div data-brand-carousel class="relative">
            <div class="mb-6 flex justify-center gap-3 md:justify-end">
                <button data-brand-prev type="button" class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:border-primary hover:text-primary" aria-label="Previous brands">@include('components.icon', ['name' => 'arrow-right', 'class' => 'w-4 h-4 rotate-180'])</button>
                <button data-brand-next type="button" class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:border-primary hover:text-primary" aria-label="Next brands">@include('components.icon', ['name' => 'arrow-right', 'class' => 'w-4 h-4'])</button>
            </div>
            <div data-brand-track class="flex snap-x gap-5 overflow-x-auto scroll-smooth pb-3 [scrollbar-width:none]">
            @foreach ($brands as $brand)
                @php
                    $logoSrc = $brand['logo_url'] ?? null;
                    if (!$logoSrc && !empty($brand['domain'])) {
                        $logoSrc = 'https://logo.clearbit.com/' . $brand['domain'];
                    }
                @endphp
                <article class="min-w-[240px] snap-start rounded-xl border border-slate-100 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg border border-slate-100 bg-slate-50 p-2">
                            @if ($logoSrc)
                                <img src="{{ $logoSrc }}" alt="{{ $brand['name'] }} logo" class="max-h-full max-w-full object-contain" loading="lazy">
                            @else
                                <span class="text-xl font-extrabold text-slate-400">{{ strtoupper(substr($brand['name'], 0, 1)) }}</span>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-900">{{ $brand['name'] }}</h3>
                            <p class="text-sm text-slate-500">{{ $brand['industry'] }}</p>
                        </div>
                    </div>
                </article>
            @endforeach
            </div>
        </div>
    </div>
</section>
