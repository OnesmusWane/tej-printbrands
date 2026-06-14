@extends('layouts.site')

@section('title', 'Tej Printbrands | Creative Design, Printing & Branding')

@section('content')
    @php
        $homePage = $pagesBySlug['home'] ?? null;
        $sec      = fn ($key) => $homePage?->sections->firstWhere('key', $key);
    @endphp
    @include('sections.hero',         ['heroSection' => $sec('hero')])
    @include('sections.services',     ['sectionData' => $sec('services')])
    @include('sections.portfolio',    ['sectionData' => $sec('portfolio')])
    @include('sections.blog')
    @include('sections.testimonials', ['sectionData' => $sec('testimonials')])
    @include('sections.brands',       ['sectionData' => $sec('brands')])
@endsection
