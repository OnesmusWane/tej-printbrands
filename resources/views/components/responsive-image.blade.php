@props([
    'src',
    'alt' => '',
    'variant' => 'card',
    'sizes' => '(min-width: 1024px) 33vw, 100vw',
    'eager' => false,
])

@php
    use App\Services\ImagePipeline;

    $src = $src ?? '';
    $isLocal = ImagePipeline::relativePathFromUrl($src) !== null;
    $srcset = null;

    if ($isLocal) {
        $thumbUrl = ImagePipeline::conversionUrl($src, 'thumb');
        $cardUrl = ImagePipeline::conversionUrl($src, 'card');
        $heroUrl = ImagePipeline::conversionUrl($src, 'hero');
        $base = match ($variant) {
            'thumb' => $thumbUrl,
            'hero' => $heroUrl,
            default => $cardUrl,
        };
        $srcset = "{$thumbUrl} 400w, {$cardUrl} 800w, {$heroUrl} 1600w";
    } elseif (str_contains($src, 'images.unsplash.com')) {
        // Unsplash serves on-the-fly resizing — just request WebP explicitly, no local conversion exists to point at.
        $sep = str_contains($src, '?') ? '&' : '?';
        $base = $src . $sep . 'fm=webp&q=75';
    } else {
        $base = $src;
    }
@endphp

<img
    src="{{ $base }}"
    @if ($srcset) srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
    alt="{{ $alt }}"
    loading="{{ $eager ? 'eager' : 'lazy' }}"
    @if ($eager) fetchpriority="high" @endif
    decoding="async"
    {{ $attributes }}
>
