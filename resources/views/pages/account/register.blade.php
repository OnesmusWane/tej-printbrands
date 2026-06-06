@extends('layouts.site')

@section('title', 'Create Account | Tej Printbrands')

@section('content')
    <section class="bg-slate-950 pt-36 pb-20">
        <div class="mx-auto max-w-3xl px-4 text-center">
            <h1 class="text-4xl font-extrabold text-white md:text-5xl">Create your account</h1>
            <p class="mt-4 text-slate-300">Track purchases, payment confirmations, and order status in one place.</p>
        </div>
    </section>
    <section class="bg-slate-50 py-16">
        <div class="mx-auto max-w-lg px-4">
            <form action="{{ route('register.submit') }}" method="post" class="rounded-2xl border border-slate-100 bg-white p-8 shadow-xl">
                @csrf
                <div class="space-y-5">
                    <label class="block space-y-2 text-sm font-semibold text-slate-700">Full Name<input name="name" value="{{ old('name') }}" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></label>
                    <label class="block space-y-2 text-sm font-semibold text-slate-700">Email<input type="email" name="email" value="{{ old('email') }}" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></label>
                    <label class="block space-y-2 text-sm font-semibold text-slate-700">Phone<input name="phone" value="{{ old('phone') }}" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></label>
                    <label class="block space-y-2 text-sm font-semibold text-slate-700">Password<input type="password" name="password" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></label>
                    <label class="block space-y-2 text-sm font-semibold text-slate-700">Confirm Password<input type="password" name="password_confirmation" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></label>
                </div>
                @if ($errors->any())
                    <div class="my-5 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
                @endif
                <button class="mt-6 w-full rounded-lg bg-primary px-6 py-3 font-extrabold text-white transition hover:bg-dark-cyan">Create Account</button>
                <p class="mt-6 text-center text-sm text-slate-600">Already registered? <a href="{{ route('login') }}" class="font-bold text-primary">Log in</a></p>
            </form>
        </div>
    </section>
@endsection
