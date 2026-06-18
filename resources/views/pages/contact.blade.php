@extends('layouts.site')

@section('title', 'Contact | Tej Printbrands')

@section('content')
    @php
        $contactPage    = $pagesBySlug['contact'] ?? null;
        $heroSec        = $contactPage?->sections->firstWhere('key', 'hero');
        $contactInfoSec = $contactPage?->sections->firstWhere('key', 'contact-info');
        $faqsSec        = $contactPage?->sections->firstWhere('key', 'faqs');
        $formSec        = $contactPage?->sections->firstWhere('key', 'form');
        $ct          = $siteSettings['contact'] ?? [];
        $address     = $ct['address']  ?? 'Nairobi, Kenya';
        $phone       = $ct['phone']    ?? '+254 700 000 000';
        $email       = $ct['email']    ?? 'info@tejprintbrands.com';
        $email2      = $ct['support_email'] ?? 'support@tejprintbrands.com';
        $hours       = $ct['hours']    ?? 'Mon-Fri, 9am - 6pm';
    @endphp
    <section class="relative overflow-hidden bg-dark pt-36 pb-20 text-center">
        <div class="absolute inset-0 bg-gradient-to-b from-cyan-900/20 to-transparent"></div>
        <div class="relative z-10 mx-auto max-w-3xl px-4">
            <h1 class="mb-6 text-4xl font-extrabold text-white md:text-5xl lg:text-6xl">{{ $heroSec?->heading ?? 'Get in Touch' }}</h1>
            <p class="text-xl text-slate-300">{{ $heroSec?->subtext ?? "We're here to help bring your ideas to life." }}</p>
        </div>
    </section>
    <section class="bg-light py-20">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:gap-20 lg:px-8">
            <div class="space-y-12">
                <div>
                    <h2 class="mb-8 text-3xl font-extrabold text-slate-900">{{ $contactInfoSec?->heading ?? 'Contact Information' }}</h2>
                    <div class="space-y-6">
                        <div class="flex items-start gap-4"><div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan">@include('components.icon', ['name' => 'map-pin', 'class' => 'w-6 h-6'])</div><div><h3 class="text-lg font-extrabold text-dark">Visit Our Studio</h3><p class="mt-1 text-slate-600">{{ $address }}</p></div></div>
                        <div class="flex items-start gap-4"><div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan">@include('components.icon', ['name' => 'phone', 'class' => 'w-6 h-6'])</div><div><h3 class="text-lg font-extrabold text-dark">Call Us</h3><p class="mt-1 text-slate-600">{{ $phone }}<br>{{ $hours }}</p></div></div>
                        <div class="flex items-start gap-4"><div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan">@include('components.icon', ['name' => 'mail', 'class' => 'w-6 h-6'])</div><div><h3 class="text-lg font-extrabold text-dark">Email Us</h3><p class="mt-1 text-slate-600">{{ $email }}<br>{{ $email2 }}</p></div></div>
                    </div>
                </div>
                <div>
                    <h2 class="mb-6 text-2xl font-extrabold text-slate-900">{{ $faqsSec?->heading ?? 'Frequently Asked Questions' }}</h2>
                    <div class="space-y-4">
                        @forelse ($faqs as $faq)
                            <details class="group overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
                                <summary class="flex cursor-pointer list-none items-center justify-between p-5 font-semibold text-slate-900 transition hover:text-primary"><span>{{ $faq->question }}</span><span class="transition group-open:rotate-180">⌄</span></summary>
                                <p class="px-5 pb-5 leading-relaxed text-slate-600">{{ $faq->answer }}</p>
                            </details>
                        @empty
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-100 bg-white p-8 shadow-xl md:p-10">
                <h2 class="mb-6 text-2xl font-extrabold text-slate-900">{{ $formSec?->heading ?? 'Send a Message' }}</h2>
                <form class="space-y-6" method="post" action="#">
                    @csrf
                    <div class="grid gap-6 md:grid-cols-2"><label class="space-y-2 text-sm font-medium text-slate-700">First Name<input type="text" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="John"></label><label class="space-y-2 text-sm font-medium text-slate-700">Last Name<input type="text" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="Doe"></label></div>
                    <label class="block space-y-2 text-sm font-medium text-slate-700">Email Address<input type="email" class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="john@example.com"></label>
                    <label class="block space-y-2 text-sm font-medium text-slate-700">Subject / Service Interest<select class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"><option>General Inquiry</option><option>Graphic Design</option><option>Printing Services</option><option>Signage</option><option>Promotional Items</option></select></label>
                    <label class="block space-y-2 text-sm font-medium text-slate-700">Message<textarea rows="5" class="w-full resize-none rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="How can we help you?"></textarea></label>
                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-6 py-4 text-lg font-extrabold text-white shadow-lg shadow-primary/20 transition hover:-translate-y-0.5 hover:bg-dark-cyan">@include('components.icon', ['name' => 'send', 'class' => 'w-5 h-5']) Send Message</button>
                </form>
            </div>
        </div>
    </section>
@endsection
