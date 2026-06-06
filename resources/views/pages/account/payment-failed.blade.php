@extends('layouts.site')

@section('title', 'Payment Failed | Tej Printbrands')

@section('content')
    <section class="bg-red-600 pt-36 pb-20 text-center text-white">
        <div class="mx-auto max-w-3xl px-4">
            <h1 class="text-4xl font-extrabold md:text-5xl">M-Pesa prompt failed</h1>
            <p class="mt-4 text-red-50">No worries. You can retry or enter your M-Pesa code manually after paying.</p>
            <a href="{{ route('account.orders.mpesa', $order) }}" class="mt-8 inline-block rounded-lg bg-white px-6 py-3 font-extrabold text-red-700">Retry M-Pesa Confirmation</a>
        </div>
    </section>
@endsection
