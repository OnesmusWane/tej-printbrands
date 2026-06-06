@extends('layouts.site')

@section('title', 'Login | Tej Printbrands')

@section('content')
    <section class="bg-slate-950 pt-36 pb-20">
        <div class="mx-auto max-w-3xl px-4 text-center">
            <h1 class="text-4xl font-extrabold text-white md:text-5xl">Log in to your account</h1>
            <p class="mt-4 text-slate-300">Purchases require an account. Service bookings and quotes are still available without logging in.</p>
        </div>
    </section>
    <section class="bg-slate-50 py-16">
        <div class="mx-auto max-w-lg px-4">
            @if (session('auth_notice'))
                <div class="mb-6 rounded-xl border border-cyan-200 bg-cyan-50 p-4 text-sm font-semibold text-slate-700">{{ session('auth_notice') }}</div>
            @endif
            <form action="{{ route('login.submit') }}" method="post" class="rounded-2xl border border-slate-100 bg-white p-8 shadow-xl">
                @csrf
                <label class="mb-5 block space-y-2 text-sm font-semibold text-slate-700">Email
                    <input type="email" name="email" value="{{ old('email') }}" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                </label>
                <label class="mb-5 block space-y-2 text-sm font-semibold text-slate-700">Password
                    <input type="password" name="password" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                </label>
                <label class="mb-6 flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" class="rounded border-slate-300 text-primary"> Remember me</label>
                @if ($errors->any())
                    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
                @endif
                <button class="w-full rounded-lg bg-primary px-6 py-3 font-extrabold text-white transition hover:bg-dark-cyan">Log In</button>
                <p class="mt-6 text-center text-sm text-slate-600">No account yet? <a href="{{ route('register') }}" class="font-bold text-primary">Create one</a></p>
            </form>
        </div>
    </section>
@endsection
