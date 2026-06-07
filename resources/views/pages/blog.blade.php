@extends('layouts.site')

@section('title', 'Insights & News | Tej Printbrands')

@section('content')

    {{-- Hero --}}
    <div class="relative overflow-hidden bg-dark py-20 text-center">
        <div class="absolute inset-0 opacity-10 [background-image:linear-gradient(rgba(255,255,255,.6)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.6)_1px,transparent_1px)] [background-size:40px_40px]"></div>
        <div class="relative z-10 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <h1 class="mb-4 text-4xl font-extrabold text-white md:text-5xl lg:text-6xl">Insights &amp; <span class="text-cyan">News</span></h1>
            <p class="text-lg text-slate-400">Expert tips, industry trends, and guides on design, printing, and branding to help your business grow.</p>
        </div>
    </div>

    {{-- Filters + Grid --}}
    <section class="bg-light py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Category pills + Search --}}
            <div class="mb-10 flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-wrap gap-2" id="cat-filters">
                    <button type="button" data-cat="All" class="cat-btn rounded-full bg-cyan px-4 py-2 text-sm font-semibold text-white shadow-md transition">All</button>
                    @foreach ($categories as $cat)
                        <button type="button" data-cat="{{ $cat }}" class="cat-btn rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">{{ $cat }}</button>
                    @endforeach
                </div>
                <div class="relative w-full md:w-72">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input id="blog-search" type="text" placeholder="Search articles…" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-700 outline-none transition focus:border-cyan focus:ring-2 focus:ring-cyan/20">
                </div>
            </div>

            {{-- Cards --}}
            @if ($posts->isEmpty())
                <div class="py-24 text-center text-slate-400">No articles published yet.</div>
            @else
                <div id="blog-grid" class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($posts as $post)
                        <article
                            class="blog-card group flex h-full cursor-pointer flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition-all hover:shadow-xl"
                            data-cat="{{ $post->category ?? '' }}"
                            data-title="{{ strtolower($post->title) }}"
                            data-excerpt="{{ strtolower($post->excerpt ?? '') }}"
                        >
                            <a href="{{ route('blog.detail', $post->slug) }}" class="flex flex-1 flex-col">
                                <div class="relative h-56 overflow-hidden">
                                    @if ($post->image_url)
                                        <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center bg-linear-to-br from-cyan-50 to-slate-100">
                                            <svg class="h-12 w-12 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                                        </div>
                                    @endif
                                    @if ($post->category)
                                        <span class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-bold text-cyan shadow-sm backdrop-blur-sm">{{ $post->category }}</span>
                                    @endif
                                </div>

                                <div class="flex flex-1 flex-col p-6">
                                    <div class="mb-4 flex items-center gap-4 text-xs text-slate-400">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                            {{ $post->published_at?->format('M d, Y') ?? '—' }}
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            {{ $post->author ?? 'Tej Printbrands' }}
                                        </span>
                                    </div>

                                    <h3 class="mb-3 line-clamp-2 text-xl font-extrabold text-slate-900 transition-colors group-hover:text-cyan">{{ $post->title }}</h3>

                                    @if ($post->excerpt)
                                        <p class="mb-4 line-clamp-3 flex-1 text-sm leading-relaxed text-slate-500">{{ $post->excerpt }}</p>
                                    @else
                                        <div class="flex-1"></div>
                                    @endif

                                    <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-4">
                                        @if ($post->read_time)
                                            <span class="text-xs font-medium text-slate-400">{{ $post->read_time }}</span>
                                        @else
                                            <span></span>
                                        @endif
                                        <span class="flex items-center gap-1 text-sm font-semibold text-cyan transition-all group-hover:gap-2">
                                            Read More
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </article>
                    @endforeach
                </div>
                <p id="blog-empty" class="hidden py-20 text-center text-slate-400">No articles match your search.</p>
            @endif

        </div>
    </section>

    <script>
    (function () {
        var cards    = document.querySelectorAll('.blog-card');
        var catBtns  = document.querySelectorAll('.cat-btn');
        var searchEl = document.getElementById('blog-search');
        var emptyEl  = document.getElementById('blog-empty');
        var activecat = 'All';

        function filter() {
            var q = searchEl ? searchEl.value.trim().toLowerCase() : '';
            var visible = 0;
            cards.forEach(function (card) {
                var cat   = card.getAttribute('data-cat') || '';
                var title = card.getAttribute('data-title') || '';
                var exerp = card.getAttribute('data-excerpt') || '';
                var matchCat  = activecat === 'All' || cat === activecat;
                var matchSrch = !q || title.includes(q) || exerp.includes(q);
                var show = matchCat && matchSrch;
                card.classList.toggle('hidden', !show);
                if (show) visible++;
            });
            if (emptyEl) emptyEl.classList.toggle('hidden', visible > 0 || cards.length === 0);
        }

        catBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                activecat = btn.getAttribute('data-cat') || 'All';
                catBtns.forEach(function (b) {
                    var isActive = b === btn;
                    b.classList.toggle('bg-cyan', isActive);
                    b.classList.toggle('text-white', isActive);
                    b.classList.toggle('shadow-md', isActive);
                    b.classList.toggle('border-slate-200', !isActive);
                    b.classList.toggle('bg-white', !isActive);
                    b.classList.toggle('text-slate-600', !isActive);
                });
                filter();
            });
        });

        if (searchEl) searchEl.addEventListener('input', filter);
    })();
    </script>
@endsection
