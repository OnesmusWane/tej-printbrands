@extends('layouts.site')

@section('title', 'Order ' . $order->order_number . ' | Tej Printbrands')

@section('content')
    <section class="bg-slate-950 pt-36 pb-16"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"><h1 class="text-4xl font-extrabold text-white">{{ $order->order_number }}</h1><p class="mt-3 text-slate-300">{{ str_replace('_', ' ', ucfirst($order->status)) }}</p></div></section>
    <section class="bg-slate-50 py-16">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[260px_1fr] lg:px-8">
            @include('partials.account-nav')
            <div class="grid gap-8 lg:grid-cols-[1fr_320px]">
                <div class="rounded-2xl bg-white p-6 shadow-sm">
                    <h2 class="mb-5 text-2xl font-extrabold text-slate-900">Items</h2>
                    <div class="space-y-4">@foreach ($order->items as $item)<div class="rounded-xl border border-slate-100 p-4"><div class="flex justify-between gap-4"><span class="font-bold text-slate-900">{{ $item['product']['name'] }}</span><span>KES {{ number_format($item['line_total']) }}</span></div><p class="mt-1 text-sm text-slate-500">{{ $item['quantity'] }} x {{ $item['finish'] }}</p></div>@endforeach</div>
                </div>
                <aside class="space-y-5">
                    <div class="rounded-2xl bg-white p-6 shadow-sm"><h3 class="mb-4 text-xl font-extrabold text-slate-900">Summary</h3><p class="flex justify-between text-sm"><span>Subtotal</span><strong>KES {{ number_format($order->subtotal) }}</strong></p><p class="mt-2 flex justify-between text-sm"><span>Handling</span><strong>KES {{ number_format($order->service_fee) }}</strong></p><p class="mt-4 border-t pt-4 text-2xl font-extrabold text-primary">KES {{ number_format($order->total) }}</p></div>
                    @if ($order->payment_method === 'mpesa' && $order->payment_status !== 'paid')<a href="{{ route('account.orders.mpesa', $order) }}" class="block rounded-lg bg-primary px-5 py-3 text-center font-extrabold text-white">Complete M-Pesa Payment</a>@endif
                </aside>
            </div>
        </div>
    </section>
@endsection
