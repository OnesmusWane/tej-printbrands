<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\PortfolioProject;
use App\Models\PricingTier;
use App\Models\ProcessStep;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\SitePage;
use App\Models\SiteSection;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use Illuminate\Support\Collection;

class SiteContentService
{
    public function sharedData(): array
    {
        return [
            'siteSettings' => SiteSetting::pluck('value', 'key')->all(),
            'pagesBySlug' => SitePage::with('sections')->get()->keyBy('slug'),
            'services' => $this->services(),
            'portfolioItems' => $this->portfolio(),
            'testimonials' => $this->testimonials(),
            'brands' => $this->brands(),
            'premiumProducts' => $this->products(),
        ];
    }

    public function page(string $slug): ?SitePage
    {
        return SitePage::with('sections')->where('slug', $slug)->first();
    }

    public function section(SitePage|Collection|array|null $page, string $key): ?SiteSection
    {
        if ($page instanceof SitePage) {
            return $page->sections->firstWhere('key', $key);
        }

        return null;
    }

    public function services(): Collection
    {
        return Service::where('is_visible', true)->orderBy('sort_order')->get();
    }

    public function processSteps(): Collection
    {
        return ProcessStep::where('is_visible', true)->orderBy('sort_order')->get();
    }

    public function pricingTiers(): Collection
    {
        return PricingTier::where('is_visible', true)->orderBy('sort_order')->get();
    }

    public function portfolio(): Collection
    {
        return PortfolioProject::where('is_visible', true)->orderBy('sort_order')->get();
    }

    public function gallery(): Collection
    {
        return GalleryItem::where('is_visible', true)->orderBy('sort_order')->get();
    }

    public function testimonials(): Collection
    {
        return Testimonial::where('status', 'approved')->latest('submitted_at')->get();
    }

    public function brands(): Collection
    {
        return Brand::where('is_visible', true)->orderBy('sort_order')->get();
    }

    public function faqs(string $page = 'contact'): Collection
    {
        return Faq::where('page_slug', $page)->where('is_visible', true)->orderBy('sort_order')->get();
    }

    public function products(): Collection
    {
        return Product::with(['category', 'options'])->where('is_visible', true)->orderBy('sort_order')->get();
    }

    public function productCategories(): Collection
    {
        return ProductCategory::orderBy('sort_order')->get();
    }
}
