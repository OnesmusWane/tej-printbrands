@extends('layouts.site')

@section('title', 'Checkout | Tej Printbrands')

@section('content')
    <section class="bg-slate-950 pt-36 pb-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <a href="{{ route('cart') }}" class="mb-8 inline-flex items-center gap-2 text-sm font-bold text-cyan-200 hover:text-white">@include('components.icon', ['name' => 'arrow-right', 'class' => 'w-4 h-4 rotate-180']) Back to cart</a>
            <p class="mb-4 text-sm font-extrabold uppercase tracking-[0.25em] text-primary">Secure Checkout</p>
            <h1 class="max-w-3xl text-4xl font-extrabold text-white md:text-6xl">Complete your order with an account.</h1>
            <p class="mt-5 max-w-2xl text-lg text-slate-300">You are signed in, so this order will be saved to your purchase history.</p>
        </div>
    </section>

    <section class="bg-slate-50 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('checkout_success'))
                @php($success = session('checkout_success'))
                <div class="mb-8 rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-emerald-900 shadow-sm">
                    <h2 class="mb-2 text-2xl font-extrabold">Order request received: {{ $success['order_number'] }}</h2>
                    <p class="text-sm">Estimated total: <strong>KES {{ number_format($success['totals']['total']) }}</strong>. Payment option: <strong>{{ strtoupper($success['payment_method']) }}</strong>. We will send the next payment and artwork confirmation steps to your account email.</p>
                    <a href="{{ route('products') }}" class="mt-5 inline-block rounded-lg bg-primary px-5 py-3 font-extrabold text-white transition hover:bg-dark-cyan">Continue Shopping</a>
                </div>
            @elseif (count($cartItems) === 0)
                <div class="rounded-2xl border border-slate-100 bg-white p-10 text-center shadow-lg">
                    <h2 class="mb-3 text-2xl font-extrabold text-slate-900">Your cart is empty.</h2>
                    <a href="{{ route('products') }}" class="inline-block rounded-lg bg-primary px-6 py-3 font-extrabold text-white transition hover:bg-dark-cyan">Browse Premium Products</a>
                </div>
            @else
                <div class="grid gap-10 lg:grid-cols-[1fr_380px]">
                    <form action="{{ route('checkout.submit') }}" method="post" class="rounded-2xl border border-slate-100 bg-white p-6 shadow-xl md:p-8">
                        @csrf
                        <div class="mb-8">
                            <h2 class="mb-2 text-2xl font-extrabold text-slate-900">Delivery & Payment</h2>
                            <p class="text-sm text-slate-600">Signed in as <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }}).</p>
                        </div>

                        <div class="mt-7 grid gap-5 md:grid-cols-2">
                            <label class="space-y-2 text-sm font-semibold text-slate-700">Phone / M-Pesa Number
                                <input type="tel" name="phone" value="{{ old('phone') }}" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="+254...">
                            </label>
                            <label class="space-y-2 text-sm font-semibold text-slate-700">Delivery Method
                                <select name="delivery_method" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                                    <option value="pickup" @selected(old('delivery_method') === 'pickup')>Pickup from studio</option>
                                    <option value="delivery" @selected(old('delivery_method') === 'delivery')>Delivery / courier</option>
                                </select>
                            </label>
                        </div>

                        <label class="mt-5 block space-y-2 text-sm font-semibold text-slate-700">Delivery Address
                            <input type="text" name="address" value="{{ old('address') }}" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Required for delivery orders">
                        </label>

                        <div class="mt-7">
                            <span class="mb-3 block text-sm font-bold text-slate-700">Payment Option</span>
                            <div class="grid gap-3 md:grid-cols-3">
                                @foreach ([['mpesa', 'M-Pesa', 'STK prompt or till instructions'], ['bank', 'Bank Transfer', 'Invoice and account details'], ['card', 'Card', 'Secure card gateway ready']] as $option)
                                    <label class="cursor-pointer rounded-xl border p-4 transition has-[:checked]:border-primary has-[:checked]:bg-cyan-50">
                                        <input type="radio" name="payment_method" value="{{ $option[0] }}" class="sr-only" @checked(old('payment_method', 'mpesa') === $option[0])>
                                        <span class="block font-extrabold text-slate-900">{{ $option[1] }}</span>
                                        <span class="mt-1 block text-xs leading-relaxed text-slate-600">{{ $option[2] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <label class="mt-5 block space-y-2 text-sm font-semibold text-slate-700">Order Notes
                            <textarea name="notes" rows="4" class="w-full resize-none rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Artwork links, deadline, special finishing, or delivery notes.">{{ old('notes') }}</textarea>
                        </label>

                        @if ($errors->any())
                            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                <strong class="block">Please fix the following:</strong>
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <button type="submit" class="mt-7 w-full rounded-lg bg-secondary px-6 py-4 text-lg font-extrabold text-white shadow-xl shadow-secondary/20 transition hover:-translate-y-0.5 hover:bg-red-600">Place Order Request</button>
                    </form>

                    <aside class="space-y-6">
                        <div class="rounded-2xl border border-slate-100 bg-white p-7 shadow-lg">
                            <h3 class="mb-5 text-xl font-extrabold text-slate-900">Order Summary</h3>
                            <div class="space-y-4">
                                @foreach ($cartItems as $item)
                                    <div class="border-b border-slate-100 pb-4 text-sm">
                                        <div class="flex justify-between gap-4">
                                            <span class="font-bold text-slate-900">{{ $item['product']['name'] }}</span>
                                            <span class="font-bold text-slate-900">KES {{ number_format($item['line_total']) }}</span>
                                        </div>
                                        <p class="mt-1 text-slate-500">{{ $item['quantity'] }} x {{ $item['finish'] }}</p>
                                    </div>
                                @endforeach
                                <div class="flex justify-between text-sm"><span class="text-slate-600">Subtotal</span><span class="font-bold text-slate-900">KES {{ number_format($cartTotals['subtotal']) }}</span></div>
                                <div class="flex justify-between text-sm"><span class="text-slate-600">Service handling</span><span class="font-bold text-slate-900">KES {{ number_format($cartTotals['service_fee']) }}</span></div>
                                <div class="border-t border-slate-200 pt-4">
                                    <span class="block text-sm text-slate-600">Estimated total</span>
                                    <span class="text-3xl font-extrabold text-primary">KES {{ number_format($cartTotals['total']) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-slate-900 p-7 text-white shadow-xl">
                            <h3 class="mb-4 text-xl font-extrabold">Payment flow</h3>
                            <p class="text-sm leading-relaxed text-slate-300">After submission, the site records the order request against your account and shows the chosen payment path. Live gateway APIs can be connected here for M-Pesa STK push, bank proof upload, or card authorization.</p>
                        </div>
                    </aside>
                </div>
            @endif
        </div>
    </section>
@endsection
