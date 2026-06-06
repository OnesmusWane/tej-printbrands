@php
    $h   = $heroSection;
    $cfg = $h?->settings ?? [];
    $heading      = $h?->heading  ?? 'Creative Design, Professional Printing & Business Branding Solutions.';
    $subtext      = $h?->subtext  ?? 'Tej Printbrands is your trusted partner in turning ideas into impactful visual solutions. From concept to final print, we deliver excellence.';
    $ctaPrimary   = $cfg["cta_primary"]   ?? 'Request a Quote';
    $ctaSecondary = $cfg["cta_secondary"] ?? 'Book a Service';
    $ctaTertiary  = $cfg["cta_tertiary"]  ?? 'View Our Work';
@endphp
<section id="home" class="relative flex min-h-[90vh] items-center overflow-hidden bg-slate-950 pt-32 pb-24 lg:pt-44">
    @if (!empty($h?->image_url))
        <img src="{{ $h->image_url }}" alt="Professional printing equipment" class="absolute inset-0 h-full w-full object-cover">
    @endif
    <div class="absolute inset-0 bg-gradient-to-r from-slate-950/90 via-slate-900/70 to-slate-900/25"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent"></div>

    <div class="relative z-10 mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl motion-safe:animate-fade-up">
            <h1 class="mb-6 text-4xl font-extrabold leading-[1.03] text-white md:text-5xl lg:text-6xl">{{ $heading }}</h1>
            <p class="mb-9 max-w-xl text-lg leading-relaxed text-cyan-50/80 md:text-xl">{{ $subtext }}</p>
            <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap">
                <a href="{{ route("booking", ["type" => "quote"]) }}" class="rounded-lg bg-red px-8 py-4 text-center font-bold text-white shadow-lg shadow-red/30 transition hover:-translate-y-1 hover:bg-red-600">{{ $ctaPrimary }}</a>
                <a href="{{ route("booking", ["type" => "booking"]) }}" class="rounded-lg bg-cyan px-8 py-4 text-center font-bold text-white shadow-lg shadow-cyan/30 transition hover:-translate-y-1 hover:bg-cyan-600">{{ $ctaSecondary }}</a>
                <a href="{{ route("work") }}" class="rounded-lg border-2 border-white/20 bg-transparent px-8 py-4 text-center font-bold text-white backdrop-blur-sm transition hover:-translate-y-1 hover:bg-white/10">{{ $ctaTertiary }}</a>
            </div>
        </div>
    </div>

    <div class="absolute bottom-0 left-0 z-20 w-full overflow-hidden leading-none">
        <svg class="block h-[70px] w-full md:h-[110px]" viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true">
            <path class="fill-white" d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V120H0V95.8C59.71,118.08,130.83,121.32,201.3,114.5,242.4,110.6,282.8,99.9,321.39,56.44Z"/>
        </svg>
    </div>
</section>
