@extends('layouts.site')

@section('title', $post->title . ' | Tej Printbrands')

@section('content')

    {{-- Back link + article wrapper --}}
    <div class="bg-light pt-10 pb-20">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

            <a href="{{ route('blog') }}" class="mb-8 inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition hover:text-cyan">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16l-4-4m0 0l4-4m-4 4h18"/></svg>
                Back to Blog
            </a>

            {{-- Article card --}}
            <article class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-xl">

                {{-- Meta header --}}
                <div class="px-8 pt-10 pb-6 text-center md:px-16">
                    @if ($post->category)
                        <span class="mb-5 inline-block rounded-full bg-cyan/10 px-4 py-1.5 text-xs font-extrabold uppercase tracking-widest text-cyan">{{ $post->category }}</span>
                    @endif

                    <h1 class="mb-6 text-3xl font-extrabold leading-tight text-slate-900 md:text-4xl lg:text-5xl">{{ $post->title }}</h1>

                    <div class="flex flex-wrap items-center justify-center gap-5 text-sm text-slate-400">
                        <span class="flex items-center gap-1.5">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            {{ $post->author ?? 'Tej Printbrands' }}
                        </span>
                        <span class="flex items-center gap-1.5">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            {{ $post->published_at?->format('F d, Y') ?? '—' }}
                        </span>
                        @if ($post->read_time)
                            <span class="flex items-center gap-1.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                {{ $post->read_time }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Hero image --}}
                @if ($post->image_url)
                    <div class="mx-8 mb-0 overflow-hidden rounded-2xl md:mx-16">
                        <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="h-64 w-full object-cover md:h-80 lg:h-96">
                    </div>
                @endif

                {{-- Content --}}
                <div class="px-8 py-10 md:px-16">
                    @if ($post->content)
                        <div class="prose prose-slate max-w-none prose-headings:font-extrabold prose-headings:text-slate-900 prose-p:leading-relaxed prose-p:text-slate-600 prose-a:text-cyan prose-a:no-underline hover:prose-a:underline prose-img:rounded-xl prose-blockquote:border-l-cyan prose-blockquote:text-slate-500">
                            {!! $post->content !!}
                        </div>
                    @endif

                    {{-- Share bar --}}
                    <div class="mt-12 flex flex-wrap items-center gap-4 border-t border-slate-100 pt-8">
                        <span class="text-sm font-semibold text-slate-500">Share this article:</span>
                        <a
                            href="https://twitter.com/intent/tweet?text={{ urlencode($post->title) }}&url={{ urlencode(url()->current()) }}"
                            target="_blank" rel="noopener"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:border-slate-400 hover:text-slate-900"
                        >
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.26 5.632zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            Twitter
                        </a>
                        <a
                            href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(url()->current()) }}"
                            target="_blank" rel="noopener"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:border-slate-400 hover:text-slate-900"
                        >
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            LinkedIn
                        </a>
                        <a
                            href="https://wa.me/?text={{ urlencode($post->title . ' ' . url()->current()) }}"
                            target="_blank" rel="noopener"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:border-slate-400 hover:text-slate-900"
                        >
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            WhatsApp
                        </a>
                    </div>
                </div>
            </article>

            {{-- Related posts --}}
            @if ($related->isNotEmpty())
                <div class="mt-16">
                    <h2 class="mb-8 text-2xl font-extrabold text-slate-900">Related Articles</h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($related as $rel)
                            <a href="{{ route('blog.detail', $rel->slug) }}" class="group flex flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:shadow-lg">
                                <div class="relative h-44 overflow-hidden">
                                    @if ($rel->image_url)
                                        <img src="{{ $rel->image_url }}" alt="{{ $rel->title }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center bg-linear-to-br from-cyan-50 to-slate-100">
                                            <svg class="h-10 w-10 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                                        </div>
                                    @endif
                                    @if ($rel->category)
                                        <span class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-0.5 text-xs font-bold text-cyan shadow-sm">{{ $rel->category }}</span>
                                    @endif
                                </div>
                                <div class="flex flex-1 flex-col p-5">
                                    <p class="mb-1 text-xs text-slate-400">{{ $rel->published_at?->format('M d, Y') }}</p>
                                    <h3 class="line-clamp-2 font-extrabold text-slate-900 transition group-hover:text-cyan">{{ $rel->title }}</h3>
                                    @if ($rel->excerpt)
                                        <p class="mt-2 line-clamp-2 text-xs text-slate-500">{{ $rel->excerpt }}</p>
                                    @endif
                                    <span class="mt-4 flex items-center gap-1 text-xs font-semibold text-cyan">
                                        Read More
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- CTA --}}
            <div class="mt-16 rounded-2xl bg-dark p-10 text-center text-white">
                <h3 class="mb-3 text-2xl font-extrabold">Ready to bring your brand to life?</h3>
                <p class="mb-6 text-slate-400">Talk to our team about design, print, and branding solutions tailored to your business.</p>
                <a href="{{ route('booking') }}" class="inline-flex items-center gap-2 rounded-xl bg-cyan px-6 py-3 font-extrabold text-white shadow-lg shadow-cyan/25 transition hover:bg-cyan-500">
                    Book a Service
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>

        </div>
    </div>

@endsection
