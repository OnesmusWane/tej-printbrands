@extends('layouts.site')

@section('title', 'Premium Products | Tej Printbrands')

@section('content')
    <section class="relative overflow-hidden bg-slate-950 pt-36 pb-24">
        <img src="{{ asset('assets/images/printing.jpg') }}" alt="Premium print products" class="absolute inset-0 h-full w-full object-cover opacity-30">
        <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-900/95 to-slate-900/70"></div>
        <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="mb-4 text-sm font-extrabold uppercase tracking-[0.25em] text-primary">Premium Shop</p>
                <h1 class="mb-6 text-4xl font-extrabold text-white md:text-6xl">Premium print products with a guided purchase flow.</h1>
                <p class="text-lg leading-relaxed text-slate-300">Select a polished package, customize the finish, and checkout with M-Pesa, bank transfer, or card-style payment intake.</p>
            </div>
        </div>
    </section>

    <section class="bg-slate-50 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-10 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-900 md:text-4xl">Curated Premium Packages</h2>
                    <p class="mt-3 max-w-2xl text-slate-600">Designed for businesses that want print work to feel intentional, tactile, and boardroom-ready.</p>
                </div>
                <a href="{{ route('booking', ['type' => 'quote']) }}" class="w-max rounded-lg border border-primary px-5 py-3 font-extrabold text-primary transition hover:bg-primary hover:text-white">Need a custom quote?</a>
            </div>

            @if (session('cart_success'))
                <div class="mb-8 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('cart_success') }}</div>
            @endif

            <div class="grid gap-8 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($products as $product)
                    <article class="group overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-lg transition hover:-translate-y-2 hover:shadow-2xl">
                        <div class="relative h-56 overflow-hidden">
                            <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" class="h-full w-full object-cover transition duration-700 group-hover:scale-110">
                            <div class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-extrabold text-slate-900">{{ $product->category?->name }}</div>
                        </div>
                        <div class="p-6">
                            <h3 class="mb-3 text-xl font-extrabold text-slate-900">{{ $product['name'] }}</h3>
                            <p class="mb-5 text-sm leading-relaxed text-slate-600">{{ $product['description'] }}</p>
                            <div class="mb-5">
                                <span class="text-3xl font-extrabold text-primary">KES {{ number_format($product['price']) }}</span>
                                <span class="block text-sm text-slate-500">{{ $product['unit'] }}</span>
                            </div>
                            <ul class="mb-6 space-y-2 text-sm text-slate-700">
                                @foreach (array_slice($product['features'], 0, 3) as $feature)
                                    <li class="flex gap-2">@include('components.icon', ['name' => 'check', 'class' => 'w-4 h-4 text-primary shrink-0 mt-0.5']) {{ $feature }}</li>
                                @endforeach
                            </ul>
                            <form action="{{ route('cart.add', $product['slug']) }}" method="post" class="space-y-3">
                                @csrf
                                <div class="grid grid-cols-[80px_1fr] gap-3">
                                    <label class="text-xs font-bold text-slate-600">Qty
                                        <input type="number" min="1" max="1000" name="quantity" value="1" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                                    </label>
                                    <label class="text-xs font-bold text-slate-600">Finish
                                        <select name="finish" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                                            @foreach ($product['finishes'] as $finish)
                                                <option value="{{ $finish }}">{{ $finish }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                                <button type="submit" class="w-full rounded-lg bg-slate-900 px-5 py-3 text-center font-extrabold text-white transition hover:bg-slate-800">Add to Cart</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
