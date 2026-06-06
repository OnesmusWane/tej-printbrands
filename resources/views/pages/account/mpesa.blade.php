@extends('layouts.site')

@section('title', 'M-Pesa Payment | ' . $order->order_number)

@section('content')
    <section class="bg-slate-950 pt-36 pb-16">
        <div class="mx-auto max-w-4xl px-4 text-center">
            <p class="mb-4 text-sm font-extrabold uppercase tracking-[0.25em] text-primary">M-Pesa Prompt</p>
            <h1 class="text-4xl font-extrabold text-white md:text-5xl">Complete payment for {{ $order->order_number }}</h1>
            <p class="mt-4 text-slate-300">A prompt request is prepared for {{ $order->mpesa_phone }}. Confirm on your phone, then enter the M-Pesa code below.</p>
        </div>
    </section>
    <section class="bg-slate-50 py-16">
        <div class="mx-auto grid max-w-5xl gap-8 px-4 md:grid-cols-[1fr_320px]">
            <div class="rounded-2xl bg-white p-8 shadow-xl">
                <div class="mb-6 rounded-2xl border border-cyan-200 bg-cyan-50 p-6">
                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary text-2xl font-extrabold text-white">M</div>
                    <h2 class="text-2xl font-extrabold text-slate-900">Check your phone</h2>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">Enter your M-Pesa PIN on the STK prompt. If the prompt does not arrive, pay manually and paste the M-Pesa confirmation code.</p>
                </div>
                <form action="{{ route('account.orders.mpesa.confirm', $order) }}" method="post" class="space-y-4">
                    @csrf
                    <label class="block space-y-2 text-sm font-semibold text-slate-700">M-Pesa Confirmation Code
                        <input name="mpesa_code" value="{{ old('mpesa_code') }}" placeholder="e.g. RFA1B2C3D4" class="w-full rounded-lg border border-slate-300 px-4 py-3 uppercase outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </label>
                    @if ($errors->any())<div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
                    <button class="w-full rounded-lg bg-primary px-6 py-4 font-extrabold text-white hover:bg-dark-cyan">Confirm Payment</button>
                </form>
                <form action="{{ route('account.orders.mpesa.fail', $order) }}" method="post" class="mt-4">
                    @csrf
                    <button class="w-full rounded-lg border border-red-200 px-6 py-3 font-extrabold text-red-600 hover:bg-red-50">Mark Prompt as Failed</button>
                </form>
            </div>
            <aside class="rounded-2xl bg-slate-900 p-7 text-white shadow-xl">
                <h3 class="mb-5 text-xl font-extrabold">Payment Summary</h3>
                <p class="text-sm text-slate-300">Order</p>
                <p class="mb-4 font-extrabold">{{ $order->order_number }}</p>
                <p class="text-sm text-slate-300">Amount</p>
                <p class="mb-4 text-3xl font-extrabold text-primary">KES {{ number_format($order->total) }}</p>
                <p class="text-sm text-slate-300">Status</p>
                <p class="font-extrabold">{{ str_replace('_', ' ', ucfirst($order->payment_status)) }}</p>
            </aside>
        </div>
    </section>
@endsection
