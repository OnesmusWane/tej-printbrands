@extends('layouts.site')

@section('title', 'Book a Service or Request a Quote | Tej Printbrands')

@php
    $selectedPackage = old('package', request('package', ''));
    // If arriving from a pricing package link, default to "booking"
    $selectedType = old('request_type', request('type', $selectedPackage ? 'booking' : 'booking')) === 'quote' ? 'quote' : 'booking';
@endphp

@section('content')
    <section class="relative overflow-hidden bg-slate-950 pt-36 pb-20">
        <img src="{{ asset('assets/images/printing.jpg') }}" alt="Printing studio" class="absolute inset-0 h-full w-full object-cover opacity-35">
        <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-900/90 to-slate-900/70"></div>
        <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="mb-4 text-sm font-extrabold uppercase tracking-[0.25em] text-primary">Project Intake</p>
                <h1 class="mb-6 text-4xl font-extrabold text-white md:text-6xl">Book a service or request a tailored quote.</h1>
                <p class="max-w-2xl text-lg leading-relaxed text-slate-300">Tell us what you need, your timeline, and the level of finish you want. We will confirm availability, pricing, and production details before anything moves forward.</p>
            </div>
        </div>
    </section>

    <section class="bg-slate-50 py-20">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[1fr_380px] lg:px-8">
            <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-xl md:p-8">
                @if (session('booking_success'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('booking_success') }}</div>
                @endif

                @if ($selectedPackage)
                    <div class="mb-6 flex items-center gap-4 rounded-xl border border-cyan-200 bg-cyan-50 p-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-white">
                            @include('components.icon', ['name' => 'check', 'class' => 'w-5 h-5'])
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-primary">Selected Package</p>
                            <p class="text-lg font-extrabold text-slate-900">{{ $selectedPackage }}</p>
                        </div>
                    </div>
                @endif

                <form action="{{ route('booking.submit') }}" method="post" class="space-y-7">
                    @csrf
                    <input type="hidden" name="package" value="{{ $selectedPackage }}">
                    <div>
                        <span class="mb-3 block text-sm font-bold text-slate-700">What would you like to do?</span>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="cursor-pointer rounded-xl border p-4 transition has-[:checked]:border-primary has-[:checked]:bg-cyan-50">
                                <input type="radio" name="request_type" value="booking" class="sr-only" {{ $selectedType === 'booking' ? 'checked' : '' }}>
                                <span class="block text-lg font-extrabold text-slate-900">Book a Service</span>
                                <span class="mt-1 block text-sm text-slate-600">Reserve time for design, print, branding, or installation work.</span>
                            </label>
                            <label class="cursor-pointer rounded-xl border p-4 transition has-[:checked]:border-secondary has-[:checked]:bg-red-50">
                                <input type="radio" name="request_type" value="quote" class="sr-only" {{ $selectedType === 'quote' ? 'checked' : '' }}>
                                <span class="block text-lg font-extrabold text-slate-900">Request a Quote</span>
                                <span class="mt-1 block text-sm text-slate-600">Get a custom estimate for a project or bulk purchase.</span>
                            </label>
                        </div>
                        @error('request_type') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="space-y-2 text-sm font-semibold text-slate-700">Service Needed
                            <select name="service" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                                @foreach ($bookingServices as $service)
                                    <option value="{{ $service }}" @selected(old('service', request('service')) === $service)>{{ $service }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="space-y-2 text-sm font-semibold text-slate-700">Preferred Date
                            <input type="date" name="preferred_date" value="{{ old('preferred_date') }}" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                        </label>
                        <label class="space-y-2 text-sm font-semibold text-slate-700">Full Name
                            <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Your name">
                            @error('name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="space-y-2 text-sm font-semibold text-slate-700">Company
                            <input type="text" name="company" value="{{ old('company') }}" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Optional">
                        </label>
                        <label class="space-y-2 text-sm font-semibold text-slate-700">Email Address
                            <input type="email" name="email" value="{{ old('email') }}" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="you@example.com">
                            @error('email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="space-y-2 text-sm font-semibold text-slate-700">Phone / WhatsApp
                            <input type="tel" name="phone" value="{{ old('phone') }}" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="+254...">
                            @error('phone') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <label class="block space-y-2 text-sm font-semibold text-slate-700">Estimated Budget
                        <select name="budget" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                            <option value="">Select a range</option>
                            @foreach (['Below KES 10,000', 'KES 10,000 - 30,000', 'KES 30,000 - 75,000', 'KES 75,000+', 'Not sure yet'] as $budget)
                                <option value="{{ $budget }}" @selected(old('budget', request('budget')) === $budget)>{{ $budget }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block space-y-2 text-sm font-semibold text-slate-700">Project Details
                        <textarea name="message" rows="6" class="w-full resize-none rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Share quantity, sizes, material, finishing, delivery location, or any brand references.">{{ old('message') }}</textarea>
                        @error('message') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <button type="submit" class="w-full rounded-lg bg-slate-900 px-6 py-4 text-lg font-extrabold text-white shadow-xl transition hover:-translate-y-0.5 hover:bg-slate-800">Submit Request</button>
                </form>
            </div>

            <aside class="space-y-6">
                <div class="rounded-2xl bg-slate-900 p-7 text-white shadow-xl">
                    <h2 class="mb-4 text-2xl font-extrabold">What happens next?</h2>
                    <div class="space-y-5 text-sm leading-relaxed text-slate-300">
                        <p><span class="font-extrabold text-primary">01.</span> We review your details and confirm specifications.</p>
                        <p><span class="font-extrabold text-primary">02.</span> You receive a quote, timeline, and payment instructions where needed.</p>
                        <p><span class="font-extrabold text-primary">03.</span> Production begins after approval and deposit confirmation.</p>
                    </div>
                </div>
                <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-7">
                    <h3 class="mb-3 text-xl font-extrabold text-slate-900">Need products instead?</h3>
                    <p class="mb-5 text-sm leading-relaxed text-slate-600">Browse ready premium print packages and start a checkout flow with M-Pesa, bank, or card options.</p>
                    <a href="{{ route('products') }}" class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-3 font-extrabold text-white transition hover:bg-dark-cyan">View Premium Products @include('components.icon', ['name' => 'arrow-right', 'class' => 'w-4 h-4'])</a>
                </div>
            </aside>
        </div>
    </section>
@endsection
