<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PortfolioProject;
use App\Models\PricingTier;
use App\Models\ProcessStep;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceRequest;
use App\Models\SitePage;
use App\Models\SiteSection;
use App\Models\SiteSetting;
use App\Models\Task;
use App\Models\TaskColumn;
use App\Models\Testimonial;
use App\Policies\AdminOnlyPolicy;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([
            SitePage::class,
            SiteSection::class,
            SiteSetting::class,
            Service::class,
            ProcessStep::class,
            PricingTier::class,
            PortfolioProject::class,
            GalleryItem::class,
            Testimonial::class,
            Brand::class,
            Faq::class,
            ProductCategory::class,
            Product::class,
            ContactMessage::class,
            QuoteRequest::class,
            ServiceBooking::class,
            ServiceRequest::class,
            Invoice::class,
            Payment::class,
            TaskColumn::class,
            Task::class,
        ] as $model) {
            Gate::policy($model, AdminOnlyPolicy::class);
        }
    }
}
