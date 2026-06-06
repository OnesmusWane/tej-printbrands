@extends('layouts.site')

@section('title', 'Profile | Tej Printbrands')

@section('content')
    <section class="bg-slate-950 pt-36 pb-16"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"><h1 class="text-4xl font-extrabold text-white">Profile & Security</h1></div></section>
    <section class="bg-slate-50 py-16">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[260px_1fr] lg:px-8">
            @include('partials.account-nav')
            <div class="space-y-8">
                @if (session('account_success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('account_success') }}</div>@endif
                @if ($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>@endif
                <form action="{{ route('account.profile.update') }}" method="post" class="rounded-2xl bg-white p-6 shadow-sm">
                    @csrf @method('PATCH')
                    <h2 class="mb-5 text-2xl font-extrabold text-slate-900">Account Details</h2>
                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="space-y-2 text-sm font-semibold text-slate-700">Name<input name="name" value="{{ old('name', auth()->user()->name) }}" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></label>
                        <label class="space-y-2 text-sm font-semibold text-slate-700">Email<input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></label>
                        <label class="space-y-2 text-sm font-semibold text-slate-700">Phone<input name="phone" value="{{ old('phone', auth()->user()->phone) }}" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></label>
                        <label class="space-y-2 text-sm font-semibold text-slate-700">Company<input name="company" value="{{ old('company', auth()->user()->company) }}" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></label>
                        <label class="space-y-2 text-sm font-semibold text-slate-700 md:col-span-2">Address<input name="address" value="{{ old('address', auth()->user()->address) }}" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></label>
                    </div>
                    <button class="mt-6 rounded-lg bg-primary px-6 py-3 font-extrabold text-white hover:bg-dark-cyan">Save Profile</button>
                </form>
                <form action="{{ route('account.password.update') }}" method="post" class="rounded-2xl bg-white p-6 shadow-sm">
                    @csrf @method('PATCH')
                    <h2 class="mb-5 text-2xl font-extrabold text-slate-900">Change Password</h2>
                    <div class="grid gap-5 md:grid-cols-3">
                        <input type="password" name="current_password" placeholder="Current password" class="rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                        <input type="password" name="password" placeholder="New password" class="rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                        <input type="password" name="password_confirmation" placeholder="Confirm new password" class="rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <button class="mt-6 rounded-lg bg-slate-900 px-6 py-3 font-extrabold text-white hover:bg-slate-800">Update Password</button>
                </form>
                <form action="{{ route('account.delete.request') }}" method="post" class="rounded-2xl border border-red-100 bg-white p-6 shadow-sm">
                    @csrf
                    <h2 class="mb-3 text-2xl font-extrabold text-red-700">Request Account Deletion</h2>
                    <p class="mb-5 text-sm text-slate-600">This records a deletion request for admin review. Type DELETE to confirm the request.</p>
                    <input name="confirmation" placeholder="DELETE" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-red-400 focus:ring-2 focus:ring-red-100">
                    <button class="mt-5 rounded-lg bg-red-600 px-6 py-3 font-extrabold text-white hover:bg-red-700">Request Deletion</button>
                </form>
            </div>
        </div>
    </section>
@endsection
