@if ($recentBlogPosts->isNotEmpty())
<section class="bg-light py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @php
            $sectionData = $sectionData ?? null;
            $blogWords   = explode(' ', $sectionData?->heading ?? 'From Our Blog');
            $blogHalf    = (int) ceil(count($blogWords) / 2);
            $blogFirst   = implode(' ', array_slice($blogWords, 0, $blogHalf));
            $blogSecond  = implode(' ', array_slice($blogWords, $blogHalf));
            $blogDesc    = $sectionData?->subtext ?? 'Tips, insights and updates from the Tej Printbrands team.';
        @endphp
        <div class="mb-16 text-center">
            <h2 class="relative mb-4 inline-block text-3xl font-extrabold text-slate-900 md:text-4xl">
                {{ $blogFirst }} <span class="text-primary">{{ $blogSecond }}</span>
                <span class="absolute -bottom-3 left-1/4 right-1/4 h-1 rounded-full bg-primary"></span>
            </h2>
            @if (!empty($blogDesc))
                <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-slate-600">{{ $blogDesc }}</p>
            @endif
        </div>
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($recentBlogPosts as $post)
                <article class="group rounded-2xl bg-white border border-slate-100 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                    @if ($post->image_url)
                        <div class="aspect-[16/9] overflow-hidden">
                            <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
                        </div>
                    @else
                        <div class="aspect-[16/9] bg-gradient-to-br from-cyan-50 to-slate-100 flex items-center justify-center">
                            <svg class="w-12 h-12 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                        </div>
                    @endif
                    <div class="p-6">
                        @if ($post->category)
                            <span class="inline-block mb-3 text-xs font-semibold uppercase tracking-wider text-cyan px-2.5 py-1 bg-cyan-50 rounded-full">{{ $post->category }}</span>
                        @endif
                        <h3 class="mb-2 text-lg font-extrabold text-slate-900 leading-snug">{{ $post->title }}</h3>
                        @if ($post->excerpt)
                            <p class="text-sm text-slate-500 leading-relaxed mb-4 line-clamp-3">{{ $post->excerpt }}</p>
                        @endif
                        <div class="flex items-center justify-between text-xs text-slate-400 mt-4 pt-4 border-t border-slate-100">
                            <span>{{ $post->author ?? 'Tej Printbrands' }}</span>
                            <span>{{ $post->published_at?->format('M d, Y') ?? '' }}</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
