@extends('layouts.site')

@section('title', 'Payment Successful | Tej Printbrands')

@section('content')
    <section class="bg-emerald-600 pt-36 pb-20 text-center text-white">
        <div class="mx-auto max-w-3xl px-4">
            <h1 class="text-4xl font-extrabold md:text-5xl">Payment confirmed</h1>
            <p class="mt-4 text-emerald-50">M-Pesa code {{ $order->mpesa_code }} has been attached to {{ $order->order_number }}.</p>
            <a href="{{ route('account.orders.show', $order) }}" class="mt-8 inline-block rounded-lg bg-white px-6 py-3 font-extrabold text-emerald-700">View Order</a>
        </div>
    </section>
@endsection
