@php
    $breadcrumbList = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect($items)->values()->map(fn ($item, $i) => [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $item['name'],
            'item' => $item['url'],
        ])->all(),
    ];
@endphp

@push('schema')
    <script type="application/ld+json">{!! json_encode($breadcrumbList, JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
