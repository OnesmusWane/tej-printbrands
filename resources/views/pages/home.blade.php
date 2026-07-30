@extends('layouts.site')

@section('title', 'Tej Printbrands | Creative Design, Printing & Branding')
@section('canonical', route('home'))
@section('meta_description', 'Tej Printbrands delivers creative design, professional printing, and complete branding solutions in Kenya — from brand identity and signage to large-format printing and promotional products.')
@section('meta_keywords', 'printing company Kenya, graphic design Nairobi, branding agency, signage printing, promotional products, brand identity design')

@php
    $organizationSchema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $siteSettings['company']['company_name'] ?? 'Tej Printbrands',
        'description' => $siteSettings['company']['description'] ?? null,
        'logo' => $siteSettings['company']['logo_url'] ?? null,
        'url' => route('home'),
    ]);
@endphp

@push('schema')
    <script type="application/ld+json">{!! json_encode($organizationSchema, JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
    @php
        $homePage = $pagesBySlug['home'] ?? null;
        $sec      = fn ($key) => $homePage?->sections->firstWhere('key', $key);
    @endphp
    @include('sections.hero',         ['heroSection' => $sec('hero')])
    @include('sections.services',     ['sectionData' => $sec('services')])
    @include('sections.portfolio',    ['sectionData' => $sec('portfolio')])
    @include('sections.blog',          ['sectionData' => $sec('blog')])
    @include('sections.testimonials', ['sectionData' => $sec('testimonials')])
    @include('sections.brands',       ['sectionData' => $sec('brands')])
@endsection
