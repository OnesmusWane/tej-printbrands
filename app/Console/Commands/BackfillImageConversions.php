<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\GalleryItem;
use App\Models\PortfolioProject;
use App\Models\Product;
use App\Models\Service;
use App\Models\SiteSection;
use App\Models\SiteSetting;
use App\Services\ImagePipeline;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackfillImageConversions extends Command
{
    protected $signature = 'images:backfill-conversions {--dry-run : List what would be generated without writing any files}';

    protected $description = 'Generate thumb/card/hero WebP conversions for existing locally-stored uploads. Originals are never modified or deleted; hotlinked external images (Unsplash, Clearbit, etc.) are skipped since there is nothing local to convert.';

    private int $generated = 0;

    private int $skippedExternal = 0;

    private int $skippedExisting = 0;

    private int $missing = 0;

    public function handle(ImagePipeline $pipeline): int
    {
        $dryRun = (bool) $this->option('dry-run');

        foreach ([
            [SiteSection::class, ['image_url']],
            [PortfolioProject::class, ['image_url']],
            [Service::class, ['image_url']],
            [Brand::class, ['logo_url']],
            [GalleryItem::class, ['image_url']],
            [BlogPost::class, ['image_url']],
            [Product::class, ['image_url']],
        ] as [$model, $columns]) {
            foreach ($model::query()->cursor() as $record) {
                foreach ($columns as $column) {
                    $this->processUrl($record->{$column}, $pipeline, $dryRun);
                }
            }
        }

        // Product.images is a JSON array of URLs rather than a single column
        foreach (Product::query()->whereNotNull('images')->cursor() as $product) {
            foreach ((array) $product->images as $url) {
                $this->processUrl(is_string($url) ? $url : null, $pipeline, $dryRun);
            }
        }

        // Company logo/favicon live inside the 'company' SiteSetting's JSON value
        $company = SiteSetting::where('key', 'company')->first();
        if ($company) {
            foreach (['logo_url', 'favicon_url'] as $key) {
                $this->processUrl($company->value[$key] ?? null, $pipeline, $dryRun);
            }
        }

        $this->newLine();
        $this->info("Generated conversions for {$this->generated} image(s).");
        $this->line("Skipped {$this->skippedExisting} already-converted, {$this->skippedExternal} external/hotlinked.");
        if ($this->missing > 0) {
            $this->warn("{$this->missing} referenced local file(s) were missing on disk — could not convert.");
        }

        return self::SUCCESS;
    }

    private function processUrl(?string $url, ImagePipeline $pipeline, bool $dryRun): void
    {
        if (empty($url)) {
            return;
        }

        $relativePath = ImagePipeline::relativePathFromUrl($url);
        if ($relativePath === null) {
            $this->skippedExternal++;

            return;
        }

        if (! Storage::disk('public')->exists($relativePath)) {
            $this->missing++;
            $this->warn("Missing on disk, skipped: {$relativePath}");

            return;
        }

        $thumbPath = ImagePipeline::conversionPath($relativePath, 'thumb');
        if (Storage::disk('public')->exists($thumbPath)) {
            $this->skippedExisting++;

            return;
        }

        $this->line("Converting: {$relativePath}");
        $this->generated++;

        if ($dryRun) {
            return;
        }

        $pipeline->backfill($relativePath);
    }
}
