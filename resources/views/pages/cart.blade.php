@extends('layouts.site')

@section('title', 'Shopping Cart | Tej Printbrands')

@section('content')
    <section class="bg-slate-950 pt-36 pb-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="mb-4 text-sm font-extrabold uppercase tracking-[0.25em] text-primary">Premium Shop</p>
            <h1 class="text-4xl font-extrabold text-white md:text-6xl">Shopping Cart</h1>
            <p class="mt-5 max-w-2xl text-lg text-slate-300">Review multiple premium print products, adjust quantities, and continue to account-backed checkout.</p>
        </div>
    </section>

    <section class="bg-slate-50 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('cart_success'))
                <div class="mb-8 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('cart_success') }}</div>
            @endif

            @if (count($cartItems) === 0)
                <div class="rounded-2xl border border-slate-100 bg-white p-10 text-center shadow-lg">
                    <h2 class="mb-3 text-2xl font-extrabold text-slate-900">Your cart is empty.</h2>
                    <p class="mb-6 text-slate-600">Add premium products before starting checkout.</p>
                    <a href="{{ route('products') }}" class="inline-block rounded-lg bg-primary px-6 py-3 font-extrabold text-white transition hover:bg-dark-cyan">Browse Premium Products</a>
                </div>
            @else
                <form action="{{ route('cart.update') }}" method="post" class="grid gap-10 lg:grid-cols-[1fr_360px]">
                    @csrf
                    @method('PATCH')
                    <div class="space-y-4">
                        @foreach ($cartItems as $item)
                            <article class="grid gap-5 rounded-2xl border border-slate-100 bg-white p-5 shadow-sm md:grid-cols-[140px_1fr_auto]">
                                <img src="{{ $item['product']['image'] }}" alt="{{ $item['product']['name'] }}" class="h-32 w-full rounded-xl object-cover md:w-36">
                                <div>
                                    <p class="mb-1 text-xs font-extrabold uppercase tracking-wider text-primary">{{ $item['product']['category'] }}</p>
                                    <h2 class="text-xl font-extrabold text-slate-900">{{ $item['product']['name'] }}</h2>
                                    <p class="mt-2 text-sm text-slate-600">{{ $item['finish'] }}</p>
                                    <p class="mt-3 text-sm font-bold text-slate-900">KES {{ number_format($item['product']['price']) }} <span class="font-medium text-slate-500">{{ $item['product']['unit'] }}</span></p>
                                </div>
                                <div class="flex flex-col gap-3 md:items-end">
                                    <label class="text-sm font-bold text-slate-600">Quantity
                                        <input type="number" min="1" max="1000" name="items[{{ $item['key'] }}]" value="{{ $item['quantity'] }}" class="mt-1 w-28 rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                                    </label>
                                    <p class="text-lg font-extrabold text-slate-900">KES {{ number_format($item['line_total']) }}</p>
                                    <button form="remove-{{ md5($item['key']) }}" type="submit" class="text-sm font-bold text-red-600 hover:text-red-700">Remove</button>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <aside class="h-max rounded-2xl border border-slate-100 bg-white p-6 shadow-xl">
                        <h2 class="mb-5 text-2xl font-extrabold text-slate-900">Summary</h2>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between"><span class="text-slate-600">Subtotal</span><span class="font-bold text-slate-900">KES {{ number_format($cartTotals['subtotal']) }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-600">Service handling</span><span class="font-bold text-slate-900">KES {{ number_format($cartTotals['service_fee']) }}</span></div>
                            <div class="border-t border-slate-200 pt-4">
                                <span class="block text-slate-600">Estimated total</span>
                                <span class="text-3xl font-extrabold text-primary">KES {{ number_format($cartTotals['total']) }}</span>
                            </div>
                        </div>
                        <button type="submit" class="mt-6 w-full rounded-lg border border-slate-300 px-5 py-3 font-extrabold text-slate-900 transition hover:bg-slate-50">Update Cart</button>
                        <a href="{{ route('checkout') }}" class="mt-3 block rounded-lg bg-secondary px-5 py-3 text-center font-extrabold text-white shadow-lg shadow-secondary/20 transition hover:bg-red-600">Proceed to Checkout</a>
                        <a href="{{ route('products') }}" class="mt-4 block text-center text-sm font-bold text-primary hover:text-dark-cyan">Continue Shopping</a>
                    </aside>
                </form>

                @foreach ($cartItems as $item)
                    <form id="remove-{{ md5($item['key']) }}" action="{{ route('cart.remove', $item['key']) }}" method="post">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            @endif
        </div>
    </section>
@endsection
