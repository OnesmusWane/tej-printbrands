@extends('layouts.site')

@section('title', 'Account Dashboard | Tej Printbrands')

@section('content')
    <section class="bg-slate-950 pt-36 pb-16"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"><h1 class="text-4xl font-extrabold text-white">Account Dashboard</h1><p class="mt-3 text-slate-300">Welcome back, {{ auth()->user()->name }}.</p></div></section>
    <section class="bg-slate-50 py-16">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[260px_1fr] lg:px-8">
            @include('partials.account-nav')
            <div class="space-y-8">
                @if (session('account_success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('account_success') }}</div>@endif
                <div class="grid gap-5 md:grid-cols-3">
                    <div class="rounded-2xl bg-white p-6 shadow-sm"><p class="text-sm text-slate-500">Total Orders</p><p class="text-3xl font-extrabold text-slate-900">{{ auth()->user()->orders()->count() }}</p></div>
                    <div class="rounded-2xl bg-white p-6 shadow-sm"><p class="text-sm text-slate-500">Pending Payment</p><p class="text-3xl font-extrabold text-primary">{{ auth()->user()->orders()->where('payment_status', 'pending')->count() }}</p></div>
                    <div class="rounded-2xl bg-white p-6 shadow-sm"><p class="text-sm text-slate-500">Deletion Request</p><p class="text-lg font-extrabold text-slate-900">{{ auth()->user()->deletion_requested_at ? 'Requested' : 'Not requested' }}</p></div>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow-sm">
                    <div class="mb-5 flex items-center justify-between"><h2 class="text-2xl font-extrabold text-slate-900">Recent Purchases</h2><a href="{{ route('account.orders') }}" class="text-sm font-bold text-primary">View all</a></div>
                    <div class="space-y-3">@forelse ($orders as $order)<a href="{{ route('account.orders.show', $order) }}" class="flex justify-between rounded-lg border border-slate-100 p-4 hover:bg-slate-50"><span class="font-bold text-slate-900">{{ $order->order_number }}</span><span class="text-sm text-slate-600">KES {{ number_format($order->total) }}</span></a>@empty<p class="text-slate-600">No purchases yet.</p>@endforelse</div>
                </div>
            </div>
        </div>
    </section>
@endsection
