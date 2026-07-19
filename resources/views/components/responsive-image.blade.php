@props([
    'src',
    'alt' => '',
    'variant' => 'card',
    'sizes' => '(min-width: 1024px) 33vw, 100vw',
    'eager' => false,
])

@php
    use App\Services\ImagePipeline;
    use Illuminate\Support\Facades\Storage;

    $src = $src ?? '';
    $relativePath = ImagePipeline::relativePathFromUrl($src);
    $base = $src;
    $srcset = null;

    if ($relativePath !== null) {
        // Only point at conversions that actually exist on disk — e.g. a fresh
        // deploy where the backfill command hasn't run yet must not 404 on images
        // that were rendering fine (as originals) a moment ago.
        $available = [];
        foreach (['thumb' => 400, 'card' => 800, 'hero' => 1600] as $name => $width) {
            if (Storage::disk('public')->exists(ImagePipeline::conversionPath($relativePath, $name))) {
                $available[$name] = ImagePipeline::conversionUrl($src, $name) . " {$width}w";
            }
        }

        if (! empty($available)) {
            $srcset = implode(', ', $available);
            $base = Storage::disk('public')->exists(ImagePipeline::conversionPath($relativePath, $variant))
                ? ImagePipeline::conversionUrl($src, $variant)
                : $src;
        }
    } elseif (str_contains($src, 'images.unsplash.com')) {
        // Unsplash serves on-the-fly resizing — just request WebP explicitly, no local conversion exists to point at.
        $sep = str_contains($src, '?') ? '&' : '?';
        $base = $src . $sep . 'fm=webp&q=75';
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
