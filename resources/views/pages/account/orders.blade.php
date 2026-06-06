@extends('layouts.site')

@section('title', 'Purchase History | Tej Printbrands')

@section('content')
    <section class="bg-slate-950 pt-36 pb-16"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"><h1 class="text-4xl font-extrabold text-white">Purchase History</h1></div></section>
    <section class="bg-slate-50 py-16">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[260px_1fr] lg:px-8">
            @include('partials.account-nav')
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="space-y-4">
                    @forelse ($orders as $order)
                        <a href="{{ route('account.orders.show', $order) }}" class="grid gap-3 rounded-xl border border-slate-100 p-5 transition hover:bg-slate-50 md:grid-cols-[1fr_auto]">
                            <div><h2 class="font-extrabold text-slate-900">{{ $order->order_number }}</h2><p class="mt-1 text-sm text-slate-500">{{ $order->created_at->format('M d, Y') }} · {{ str_replace('_', ' ', $order->status) }} · {{ strtoupper($order->payment_method) }}</p></div>
                            <div class="font-extrabold text-primary">KES {{ number_format($order->total) }}</div>
                        </a>
                    @empty
                        <p class="text-slate-600">No purchases yet.</p>
                    @endforelse
                </div>
                <div class="mt-6">{{ $orders->links() }}</div>
            </div>
        </div>
    </section>
@endsection
