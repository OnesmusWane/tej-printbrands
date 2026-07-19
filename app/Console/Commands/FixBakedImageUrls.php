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
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FixBakedImageUrls extends Command
{
    protected $signature = 'images:fix-urls {--dry-run : List what would change without saving}';

    protected $description = 'Repair image URLs that were permanently baked with the wrong host/scheme (e.g. stored as http://localhost:8008/... because APP_URL was misconfigured at upload time). Rewrites them to the current app URL; does not touch external/hotlinked URLs.';

    private int $fixed = 0;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $currentBase = rtrim(Storage::disk('public')->url(''), '/');

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
                $dirty = false;

                foreach ($columns as $column) {
                    $fixed = $this->rewrite($record->{$column}, $currentBase);
                    if ($fixed !== null) {
                        $record->{$column} = $fixed;
                        $dirty = true;
                    }
                }

                if ($dirty && ! $dryRun) {
                    $record->save();
                }
            }
        }

        $company = SiteSetting::where('key', 'company')->first();
        if ($company) {
            $value = $company->value;
            $dirty = false;

            foreach (['logo_url', 'favicon_url'] as $key) {
                $fixed = $this->rewrite($value[$key] ?? null, $currentBase);
                if ($fixed !== null) {
                    $value[$key] = $fixed;
                    $dirty = true;
                }
            }

            if ($dirty && ! $dryRun) {
                $company->update(['value' => $value]);
            }
        }

        $this->newLine();
        $this->info(($dryRun ? '[dry-run] Would fix' : 'Fixed')." {$this->fixed} URL(s) to use {$currentBase}.");

        return self::SUCCESS;
    }

    /**
     * Returns the corrected URL if $url points at our own /storage/ mount under a
     * different host/scheme than the current one, or null if nothing needs fixing
     * (already correct, empty, or an external/hotlinked URL we shouldn't touch).
     */
    private function rewrite(?string $url, string $currentBase): ?string
    {
        if (empty($url)) {
            return null;
        }

        $marker = '/storage/';
        $pos = strpos($url, $marker);
        if ($pos === false) {
            return null; // not one of our own storage URLs
        }

        $relative = substr($url, $pos + strlen($marker));
        $correct = $currentBase.'/'.$relative;

        if ($correct === $url) {
            return null; // already correct
        }

        $this->line("Fixing: {$url} -> {$correct}");
        $this->fixed++;

        return $correct;
    }
}
