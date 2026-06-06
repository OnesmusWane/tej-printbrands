<section class="relative overflow-hidden bg-gradient-to-br from-cyan-50 via-cyan-100 to-cyan-200 py-24">
    <div class="absolute right-0 top-0 h-64 w-64 rounded-full bg-cyan-300 opacity-30 blur-3xl"></div>
    <div class="absolute bottom-0 left-0 h-64 w-64 rounded-full bg-blue-300 opacity-30 blur-3xl"></div>
    <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @include('partials.section-heading', ['kicker' => '', 'title' => $sectionData?->heading ?? 'Client Testimonials', 'description' => null, 'lightLine' => true])
        <div class="grid gap-8 md:grid-cols-3">
            @foreach ($testimonials as $testimonial)
                <article class="relative rounded-2xl bg-white p-8 shadow-lg">
                    <div class="absolute -left-2 -top-5 text-cyan-200 opacity-70">@include('components.icon', ['name' => 'quote', 'class' => 'w-16 h-16'])</div>
                    <div class="relative z-10">
                        <p class="mb-6 text-lg font-semibold italic leading-relaxed text-slate-700">"{{ $testimonial['text'] }}"</p>
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 text-xl font-bold text-white shadow-inner">{{ substr($testimonial['author'], 0, 1) }}</div>
                            <div>
                                <h4 class="font-extrabold text-slate-900">{{ $testimonial['author'] }}</h4>
                                <p class="text-sm text-primary">{{ $testimonial['role'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="absolute -bottom-4 left-10 -z-0 h-8 w-8 rotate-45 bg-white shadow-lg"></div>
                </article>
            @endforeach
        </div>
    </div>
</section>
