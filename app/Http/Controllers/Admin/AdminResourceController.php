<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminResourceRequest;
use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PortfolioProject;
use App\Models\PricingTier;
use App\Models\ProcessStep;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductOption;
use App\Models\Quotation;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminResourceController extends Controller
{
    private const MODELS = [
        'blog-posts' => BlogPost::class,
        'site-pages' => SitePage::class,
        'site-sections' => SiteSection::class,
        'site-settings' => SiteSetting::class,
        'services' => Service::class,
        'process-steps' => ProcessStep::class,
        'pricing-tiers' => PricingTier::class,
        'portfolio-projects' => PortfolioProject::class,
        'gallery-items' => GalleryItem::class,
        'testimonials' => Testimonial::class,
        'brands' => Brand::class,
        'faqs' => Faq::class,
        'product-categories' => ProductCategory::class,
        'products' => Product::class,
        'product-options' => ProductOption::class,
        'contact-messages' => ContactMessage::class,
        'quote-requests' => QuoteRequest::class,
        'service-bookings' => ServiceBooking::class,
        'service-requests' => ServiceRequest::class,
        'quotations' => Quotation::class,
        'invoices' => Invoice::class,
        'payments' => Payment::class,
        'orders' => Order::class,
        'task-columns' => TaskColumn::class,
        'tasks' => Task::class,
    ];

    public function index(Request $request, string $resource): JsonResponse
    {
        $query = $this->model($resource)::query();

        foreach (['status', 'category', 'is_visible'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        return response()->json($query->latest()->paginate((int) $request->input('per_page', 50)));
    }

    public function store(AdminResourceRequest $request, string $resource): JsonResponse
    {
        $model = $this->model($resource)::create($request->validated());

        return response()->json($model, 201);
    }

    public function show(string $resource, int $id): JsonResponse
    {
        return response()->json($this->find($resource, $id));
    }

    public function update(AdminResourceRequest $request, string $resource, int $id): JsonResponse
    {
        $model = $this->find($resource, $id);
        $model->update($request->validated());

        return response()->json($model->refresh());
    }

    public function destroy(string $resource, int $id): JsonResponse
    {
        $this->find($resource, $id)->delete();

        return response()->json(['message' => 'Deleted']);
    }

    private function find(string $resource, int $id): Model
    {
        return $this->model($resource)::findOrFail($id);
    }

    private function model(string $resource): string
    {
        abort_unless(array_key_exists($resource, self::MODELS), 404);

        return self::MODELS[$resource];
    }
}
