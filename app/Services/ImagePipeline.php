<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImagePipeline
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * Store an uploaded image plus its thumb/card/hero WebP conversions.
     * The original file is kept untouched — conversions are additive.
     *
     * @return array<string, string> disk-relative paths, keyed by 'original' and each conversion name
     */
    public function store(UploadedFile $file, string $directory, string $disk = 'public'): array
    {
        $originalPath = $file->store($directory, $disk);
        $paths = ['original' => $originalPath];

        $source = $this->manager->decodePath($file->getRealPath());

        foreach (config('images.conversions') as $name => $cfg) {
            $conversionPath = self::conversionPath($originalPath, $name);
            $encoded = (clone $source)->scaleDown(width: $cfg['width'])
                ->encodeUsingFileExtension('webp', quality: $cfg['quality']);

            Storage::disk($disk)->put($conversionPath, (string) $encoded);
            $paths[$name] = $conversionPath;
        }

        return $paths;
    }

    /**
     * Generate (or regenerate) conversions for an already-stored file, without
     * moving/re-uploading the original. Used by the backfill command.
     *
     * @return array<string, string> disk-relative paths, keyed by each conversion name
     */
    public function backfill(string $originalPath, string $disk = 'public'): array
    {
        $source = $this->manager->decodePath(Storage::disk($disk)->path($originalPath));
        $paths = [];

        foreach (config('images.conversions') as $name => $cfg) {
            $conversionPath = self::conversionPath($originalPath, $name);
            $encoded = (clone $source)->scaleDown(width: $cfg['width'])
                ->encodeUsingFileExtension('webp', quality: $cfg['quality']);

            Storage::disk($disk)->put($conversionPath, (string) $encoded);
            $paths[$name] = $conversionPath;
        }

        return $paths;
    }

    public static function conversionPath(string $originalPath, string $variant): string
    {
        $info = pathinfo($originalPath);
        $dir = ($info['dirname'] === '.') ? '' : $info['dirname'].'/';

        return $dir.$info['filename'].'-'.$variant.'.webp';
    }

    public static function conversionUrl(string $originalUrl, string $variant): string
    {
        $info = pathinfo(parse_url($originalUrl, PHP_URL_PATH) ?? $originalUrl);
        $replacement = '/'.$info['filename'].'-'.$variant.'.webp';

        return preg_replace('#/[^/]+$#', $replacement, $originalUrl);
    }

    /**
     * The relative "public" disk path for a fully-qualified storage URL,
     * or null if the URL doesn't point at our own /storage/ mount
     * (e.g. hotlinked Unsplash/Clearbit images, which have no local conversions).
     */
    public static function relativePathFromUrl(string $url): ?string
    {
        $marker = '/storage/';
        $pos = strpos($url, $marker);

        if ($pos === false) {
            return null;
        }

        return substr($url, $pos + strlen($marker));
    }
}
