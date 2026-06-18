@extends('layouts.site')

@section('title', 'Premium Products | Tej Printbrands')

@section('content')
    @php
        $shopPage    = $pagesBySlug['shop'] ?? null;
        $heroSec     = $shopPage?->sections->firstWhere('key', 'hero');
        $productsSec = $shopPage?->sections->firstWhere('key', 'products');

        $heroWords   = explode(' ', $heroSec?->heading ?? 'Premium Print Products');
        $heroHalf    = (int) ceil(count($heroWords) / 2);
        $heroFirst   = implode(' ', array_slice($heroWords, 0, $heroHalf));
        $heroSecond  = implode(' ', array_slice($heroWords, $heroHalf));

        $prodWords   = explode(' ', $productsSec?->heading ?? 'Curated Premium Packages');
        $prodHalf    = (int) ceil(count($prodWords) / 2);
        $prodFirst   = implode(' ', array_slice($prodWords, 0, $prodHalf));
        $prodSecond  = implode(' ', array_slice($prodWords, $prodHalf));

        $quoteLabel  = $productsSec?->settings['quote_label'] ?? 'Need a custom quote?';
    @endphp

    <section class="relative overflow-hidden bg-slate-950 pt-36 pb-24">
        <img src="{{ $heroSec?->image_url ?? asset('assets/images/printing.jpg') }}" alt="Premium print products" class="absolute inset-0 h-full w-full object-cover opacity-30">
        <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-900/95 to-slate-900/70"></div>
        <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="mb-4 text-sm font-extrabold uppercase tracking-[0.25em] text-primary">Premium Shop</p>
                <h1 class="mb-6 text-4xl font-extrabold text-white md:text-6xl">{{ $heroFirst }} <span class="text-primary">{{ $heroSecond }}</span></h1>
                <p class="text-lg leading-relaxed text-slate-300">{{ $heroSec?->subtext ?? 'Select a polished package, customize the finish, and checkout with M-Pesa, bank transfer, or card-style payment intake.' }}</p>
            </div>
        </div>
    </section>

    <section class="bg-slate-50 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-10 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-900 md:text-4xl">{{ $prodFirst }} <span class="text-primary">{{ $prodSecond }}</span></h2>
                    <p class="mt-3 max-w-2xl text-slate-600">{{ $productsSec?->subtext ?? 'Designed for businesses that want print work to feel intentional, tactile, and boardroom-ready.' }}</p>
                </div>
                <a href="{{ route('booking', ['type' => 'quote']) }}" class="w-max rounded-lg border border-primary px-5 py-3 font-extrabold text-primary transition hover:bg-primary hover:text-white">{{ $quoteLabel }}</a>
            </div>

            @if (session('cart_success'))
                <div class="mb-8 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('cart_success') }}</div>
            @endif

            <div class="grid gap-8 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($products as $product)
                    @php
                        $rawImgs = is_array($product['images'] ?? null) ? array_values(array_filter($product['images'])) : [];
                        if (empty($rawImgs) && !empty($product['image'])) {
                            $rawImgs = [$product['image']];
                        }
                        $coverImg  = $rawImgs[0] ?? null;
                        $extraImgs = array_slice($rawImgs, 1, 3);  // up to 3 thumbnails
                        $moreCount = max(0, count($rawImgs) - 4);  // images beyond the 4 shown
                        $hasMany   = count($rawImgs) > 1;
                    @endphp

                    <article class="group overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-lg transition hover:-translate-y-2 hover:shadow-2xl">

                        {{-- ── Image gallery area ──────────────────────────────── --}}
                        @if ($coverImg)
                            @if (!$hasMany)
                                {{-- Single image: full-width, click to lightbox --}}
                                <div class="relative h-56 overflow-hidden cursor-pointer"
                                     onclick="openLightbox('{{ $coverImg }}', '{{ addslashes($product['name']) }}')">
                                    <img src="{{ $coverImg }}" alt="{{ $product['name'] }}"
                                         class="h-full w-full object-cover transition duration-700 group-hover:scale-110">
                                    <div class="absolute inset-0 flex items-end justify-end p-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="rounded-lg bg-black/60 px-2 py-1 text-[10px] font-bold text-white backdrop-blur-sm">
                                            View full image
                                        </span>
                                    </div>
                                    <div class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-extrabold text-slate-900">{{ $product->category?->name }}</div>
                                </div>
                            @else
                                {{-- Multiple images: split gallery --}}
                                <div class="relative h-56 overflow-hidden flex gap-0.5">
                                    {{-- Main cover image (left, ~60%) --}}
                                    <div class="relative flex-[3] cursor-pointer overflow-hidden"
                                         onclick="openLightbox('{{ $coverImg }}', '{{ addslashes($product['name']) }}')">
                                        <img src="{{ $coverImg }}" alt="{{ $product['name'] }}"
                                             class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                                        <div class="absolute inset-0 bg-black/0 hover:bg-black/10 transition-colors"></div>
                                    </div>

                                    {{-- Thumbnail column (right, ~40%) --}}
                                    <div class="flex flex-[2] flex-col gap-0.5">
                                        @foreach ($extraImgs as $tIdx => $thumb)
                                            @php
                                                $isLast  = $tIdx === count($extraImgs) - 1;
                                                $showMore = $isLast && $moreCount > 0;
                                            @endphp
                                            <div class="relative flex-1 cursor-pointer overflow-hidden"
                                                 onclick="openLightbox('{{ $thumb }}', '{{ addslashes($product['name']) }}')">
                                                <img src="{{ $thumb }}" alt="{{ $product['name'] }} {{ $tIdx + 2 }}"
                                                     class="h-full w-full object-cover transition duration-300 hover:scale-105">
                                                @if ($showMore)
                                                    <div class="absolute inset-0 flex items-center justify-center bg-black/55 backdrop-blur-[1px]">
                                                        <span class="text-base font-extrabold text-white">+{{ $moreCount + 1 }}</span>
                                                    </div>
                                                @else
                                                    <div class="absolute inset-0 bg-black/0 hover:bg-black/10 transition-colors"></div>
                                                @endif
                                            </div>
                                        @endforeach
                                        {{-- Fill empty slots so column always has consistent height --}}
                                        @for ($fill = count($extraImgs); $fill < 3; $fill++)
                                            <div class="flex-1 bg-slate-100"></div>
                                        @endfor
                                    </div>

                                    {{-- Category badge --}}
                                    <div class="absolute left-3 top-3 rounded-full bg-white/90 px-3 py-1 text-xs font-extrabold text-slate-900 z-10">{{ $product->category?->name }}</div>

                                    {{-- Photo count badge --}}
                                    <div class="absolute bottom-3 left-3 flex items-center gap-1 rounded-full bg-black/60 px-2.5 py-1 text-[10px] font-bold text-white z-10 backdrop-blur-sm">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        {{ count($rawImgs) }} photos
                                    </div>
                                </div>
                            @endif
                        @else
                            {{-- No image placeholder --}}
                            <div class="relative h-56 bg-slate-100 flex items-center justify-center">
                                <svg class="h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <div class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-extrabold text-slate-900">{{ $product->category?->name }}</div>
                            </div>
                        @endif

                        {{-- ── Product info ────────────────────────────────────── --}}
                        <div class="p-6">
                            <h3 class="mb-3 text-xl font-extrabold text-slate-900">{{ $product['name'] }}</h3>
                            <p class="mb-5 text-sm leading-relaxed text-slate-600">{{ $product['description'] }}</p>
                            <div class="mb-5">
                                <span class="text-3xl font-extrabold text-primary">KES {{ number_format($product['price']) }}</span>
                                <span class="block text-sm text-slate-500">{{ $product['unit'] }}</span>
                            </div>
                            <ul class="mb-6 space-y-2 text-sm text-slate-700">
                                @foreach (array_slice($product['features'] ?? [], 0, 3) as $feature)
                                    <li class="flex gap-2">@include('components.icon', ['name' => 'check', 'class' => 'w-4 h-4 text-primary shrink-0 mt-0.5']) {{ $feature }}</li>
                                @endforeach
                            </ul>
                            <form action="{{ route('cart.add', $product['slug']) }}" method="post" class="space-y-3">
                                @csrf
                                @php $hasFinishes = !empty($product['finishes']); @endphp
                                <div class="{{ $hasFinishes ? 'grid grid-cols-[80px_1fr] gap-3' : '' }}">
                                    <label class="text-xs font-bold text-slate-600">Qty
                                        <input type="number" min="1" max="1000" name="quantity" value="1" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                                    </label>
                                    @if ($hasFinishes)
                                        <label class="text-xs font-bold text-slate-600">Finish
                                            <select name="finish" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                                                @foreach ($product['finishes'] as $finish)
                                                    <option value="{{ $finish }}">{{ $finish }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                    @else
                                        <input type="hidden" name="finish" value="standard">
                                    @endif
                                </div>
                                <button type="submit" class="w-full rounded-lg bg-slate-900 px-5 py-3 text-center font-extrabold text-white transition hover:bg-slate-800">Add to Cart</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Lightbox --}}
    <script>
    window.openLightbox = function(src, alt) {
        var overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.92);display:flex;align-items:center;justify-content:center;padding:16px;cursor:zoom-out;';
        var img = document.createElement('img');
        img.src = src; img.alt = alt || '';
        img.style.cssText = 'max-width:100%;max-height:90vh;object-fit:contain;border-radius:8px;box-shadow:0 25px 60px rgba(0,0,0,0.6);cursor:default;';
        img.onclick = function(e){ e.stopPropagation(); };
        var btn = document.createElement('button');
        btn.innerHTML = '&times;';
        btn.style.cssText = 'position:absolute;top:16px;right:20px;background:rgba(255,255,255,0.15);border:none;color:white;font-size:28px;line-height:1;width:44px;height:44px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background 0.2s;';
        btn.onmouseenter = function(){ btn.style.background='rgba(255,255,255,0.25)'; };
        btn.onmouseleave = function(){ btn.style.background='rgba(255,255,255,0.15)'; };
        overlay.appendChild(img); overlay.appendChild(btn);
        document.body.appendChild(overlay);
        function close(){ if(overlay.parentNode){ overlay.parentNode.removeChild(overlay); } }
        overlay.onclick = close; btn.onclick = close;
        document.addEventListener('keydown', function esc(e){ if(e.key==='Escape'){ close(); document.removeEventListener('keydown',esc); } });
        document.body.style.overflow = 'hidden';
        overlay.addEventListener('remove', function(){ document.body.style.overflow = ''; });
        // Restore scroll when closed
        var origClose = close;
        overlay.onclick = btn.onclick = function(e){ origClose(); document.body.style.overflow=''; };
    };
    </script>
@endsection
