@extends('layouts.site')

@section('title', 'Gallery | Tej Printbrands')

@section('content')
    <section class="min-h-screen bg-white pt-36 pb-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @php
                $galleryPage = $pagesBySlug['gallery'] ?? null;
                $heroSec     = $galleryPage?->sections->firstWhere('key', 'hero');
            @endphp
            <div class="mb-16 text-center">
                <h1 class="mb-6 text-4xl font-extrabold text-dark md:text-5xl">{{ $heroSec?->heading ?? 'Visual Inspiration Gallery' }}</h1>
                <p class="mx-auto max-w-2xl text-xl text-slate-600">{{ $heroSec?->subtext ?? 'A curated collection of our finest design, printing, and branding work.' }}</p>
            </div>
            <div class="grid auto-rows-[200px] grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($galleryImages as $image)
                    <button type="button" data-gallery-image="{{ $image['url'] }}" class="group relative overflow-hidden rounded-xl {{ $image['span'] }}" aria-label="Open gallery image {{ $loop->iteration }}">
                        <img src="{{ $image['url'] }}" alt="Gallery item {{ $loop->iteration }}" class="h-full w-full object-cover transition duration-700 group-hover:scale-110">
                        <span class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition group-hover:opacity-100"><span class="flex h-12 w-12 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur-sm">@include('components.icon', ['name' => 'zoom', 'class' => 'w-6 h-6'])</span></span>
                    </button>
                @endforeach
            </div>
        </div>
    </section>
    <div data-lightbox class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/95 p-4 sm:p-8">
        <button data-lightbox-close type="button" class="absolute right-6 top-6 rounded-full bg-black/50 p-2 text-white/70 transition hover:text-white" aria-label="Close lightbox">@include('components.icon', ['name' => 'x', 'class' => 'w-8 h-8'])</button>
        <img data-lightbox-image src="" alt="Enlarged gallery view" class="max-h-full max-w-full rounded-lg object-contain shadow-2xl">
    </div>
@endsection
