@php
    use App\Services\ImagePipeline;

    $h   = $heroSection;
    $cfg = $h?->settings ?? [];
    $heading      = $h?->heading  ?? 'Creative Design, Professional Printing & Business Branding Solutions.';
    $subtext      = $h?->subtext  ?? 'Tej Printbrands is your trusted partner in turning ideas into impactful visual solutions. From concept to final print, we deliver excellence.';
    $ctaPrimary   = $cfg["cta_primary"]   ?? 'Request a Quote';
    $ctaSecondary = $cfg["cta_secondary"] ?? 'Book a Service';
    $ctaTertiary  = $cfg["cta_tertiary"]  ?? 'View Our Work';

    $heroImageUrl = $h?->image_url;
    if ($heroImageUrl && ImagePipeline::relativePathFromUrl($heroImageUrl) !== null) {
        $heroImageUrl = ImagePipeline::conversionUrl($heroImageUrl, 'hero');
    }
@endphp

@if ($heroImageUrl)
    @push('preload')
        <link rel="preload" as="image" href="{{ $heroImageUrl }}" fetchpriority="high">
    @endpush
@endif
<section id="home" class="relative flex min-h-[90vh] items-center overflow-hidden bg-slate-950 pt-32 pb-24 lg:pt-44">
    @if (!empty($h?->image_url))
        <x-responsive-image :src="$h->image_url" alt="Professional printing equipment" variant="hero" sizes="100vw" :eager="true" class="absolute inset-0 h-full w-full object-cover" />
    @endif
    <div class="absolute inset-0 bg-gradient-to-r from-slate-950/90 via-slate-900/70 to-slate-900/25"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent"></div>

    <div class="relative z-10 mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl motion-safe:animate-fade-up">
            <h1 class="mb-6 text-4xl font-extrabold leading-[1.03] text-white md:text-5xl lg:text-6xl">{{ $heading }}</h1>
            <p class="mb-9 max-w-xl text-lg leading-relaxed text-cyan-50/80 md:text-xl">{{ $subtext }}</p>
            <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap"> 
                <a href="{{ route("booking", ["type" => "quote"]) }}" class="rounded-lg bg-red px-8 py-4 text-center font-bold text-white shadow-lg shadow-red/30 transition hover:-translate-y-1 hover:bg-red-600">{{ 'Request a Quote'}}</a>
                <a href="{{ route("booking", ["type" => "booking"]) }}" class="rounded-lg bg-cyan px-8 py-4 text-center font-bold text-white shadow-lg shadow-cyan/30 transition hover:-translate-y-1 hover:bg-cyan-600">{{ 'Book a Service' }}</a>
                <a href="{{ route("work") }}" class="rounded-lg border-2 border-white/20 bg-transparent px-8 py-4 text-center font-bold text-white backdrop-blur-sm transition hover:-translate-y-1 hover:bg-white/10">{{ 'View Our Work' }}</a>
            </div>
        </div>
    </div>

</section>

