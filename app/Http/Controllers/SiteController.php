<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\PublicSubmissionService;
use App\Services\SiteContentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SiteController extends Controller
{
    public function __construct(
        private SiteContentService $content,
        private PublicSubmissionService $submissions,
    ) {}

    public function home(): View
    {
        return view('pages.home', $this->sharedData());
    }

    public function services(): View
    {
        return view('pages.services', array_merge($this->sharedData(), [
            'detailedServices' => $this->content->services(),
            'processSteps' => $this->content->processSteps(),
            'pricingTiers' => $this->content->pricingTiers(),
        ]));
    }

    public function work(): View
    {
        return view('pages.work', array_merge($this->sharedData(), [
            'stats' => $this->workStats(),
            'caseStudies' => $this->caseStudies(),
        ]));
    }

    public function gallery(): View
    {
        return view('pages.gallery', array_merge($this->sharedData(), [
            'galleryImages' => $this->content->gallery(),
        ]));
    }

    public function contact(): View
    {
        return view('pages.contact', array_merge($this->sharedData(), [
            'faqs' => $this->content->faqs('contact'),
        ]));
    }

    public function booking(): View
    {
        return view('pages.booking', array_merge($this->sharedData(), [
            'bookingServices' => $this->content->services()->pluck('title')->all(),
        ]));
    }

    public function submitBooking(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'request_type'   => ['required', 'in:booking,quote'],
            'service'        => ['required', 'string', 'max:120'],
            'package'        => ['nullable', 'string', 'max:120'],
            'name'           => ['required', 'string', 'max:120'],
            'email'          => ['required', 'email', 'max:160'],
            'phone'          => ['required', 'string', 'max:40'],
            'company'        => ['nullable', 'string', 'max:120'],
            'preferred_date' => ['nullable', 'date'],
            'budget'         => ['nullable', 'string', 'max:80'],
            'message'        => ['required', 'string', 'max:1500'],
        ]);

        if ($validated['request_type'] === 'quote') {
            // Quote requests go to quote_requests table; product field holds the service name
            $this->submissions->createQuoteRequest([
                'name'    => $validated['name'],
                'email'   => $validated['email'],
                'phone'   => $validated['phone'],
                'product' => $validated['service'],
                'budget'  => $validated['budget'] ?? null,
                'notes'   => $validated['message'],
                'status'  => 'new',
            ]);

            $successMsg = 'Your quote request has been received. Our team will prepare a tailored estimate and contact you shortly.';
        } else {
            // Booking requests go to service_requests; admin reviews and converts to a booking
            $description = $validated['message'];
            if (!empty($validated['package'])) {
                $description = "[Package: {$validated['package']}]\n\n" . $description;
            }

            $this->submissions->createServiceRequest([
                'client'      => $validated['name'],
                'email'       => $validated['email'],
                'phone'       => $validated['phone'],
                'service'     => $validated['service'],
                'budget'      => $validated['budget'] ?? null,
                'timeline'    => $validated['preferred_date'] ?? null,
                'description' => $description,
                'priority'    => 'medium',
                'status'      => 'new',
            ]);

            $successMsg = 'Your booking request has been received. Our team will confirm availability and next steps shortly.';
        }

        return back()
            ->withInput($request->only('service', 'preferred_date'))
            ->with('booking_success', $successMsg);
    }

    public function products(): View
    {
        return view('pages.products', array_merge($this->sharedData(), [
            'products' => $this->content->products(),
        ]));
    }

    public function addToCart(Request $request, string $slug): RedirectResponse
    {
        $product = Product::where('slug', $slug)->where('is_visible', true)->firstOrFail();

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'finish' => ['required', 'string', 'max:120'],
        ]);

        $cart = session('cart', []);
        $key = $slug.'|'.$validated['finish'];

        $cart[$key] = [
            'slug' => $slug,
            'finish' => $validated['finish'],
            'quantity' => ($cart[$key]['quantity'] ?? 0) + $validated['quantity'],
        ];

        session(['cart' => $cart]);

        return redirect()->route('cart')->with('cart_success', $product->name.' has been added to your cart.');
    }

    public function cart(): View
    {
        return view('pages.cart', array_merge($this->sharedData(), [
            'cartItems' => $this->cartItems(),
            'cartTotals' => $this->cartTotals(),
        ]));
    }

    public function updateCart(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $cart = session('cart', []);

        foreach ($validated['items'] as $key => $quantity) {
            if (isset($cart[$key])) {
                $cart[$key]['quantity'] = $quantity;
            }
        }

        session(['cart' => $cart]);

        return back()->with('cart_success', 'Your cart has been updated.');
    }

    public function removeFromCart(string $key): RedirectResponse
    {
        $cart = session('cart', []);
        unset($cart[$key]);
        session(['cart' => $cart]);

        return back()->with('cart_success', 'Item removed from your cart.');
    }

    public function checkout(): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->guest(route('login'))->with('auth_notice', 'Please log in or create an account before checkout.');
        }

        if (count($this->cartItems()) === 0 && ! session('checkout_success')) {
            return redirect()->route('products')->with('cart_success', 'Add at least one premium product before checkout.');
        }

        return view('pages.checkout', array_merge($this->sharedData(), [
            'cartItems' => $this->cartItems(),
            'cartTotals' => $this->cartTotals(),
        ]));
    }

    public function legacyCheckout(string $_slug): RedirectResponse
    {
        return redirect()->route('products')->with('cart_success', 'Choose quantity and finish, then add the product to your cart.');
    }

    public function submitCheckout(Request $request): RedirectResponse
    {
        if (count($this->cartItems()) === 0) {
            return redirect()->route('products')->with('cart_success', 'Your cart is empty.');
        }

        if (! Auth::check()) {
            return redirect()->guest(route('login'))->with('auth_notice', 'Please log in or create an account before checkout.');
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:40'],
            'delivery_method' => ['required', 'in:pickup,delivery'],
            'address' => ['nullable', 'string', 'max:300'],
            'payment_method' => ['required', 'in:mpesa,bank,card'],
            'notes' => ['nullable', 'string', 'max:800'],
        ]);

        $totals = $this->cartTotals();
        $order = Order::create([
            'user_id' => Auth::id(),
            'order_number' => 'TPB-'.now()->format('ymd').'-'.strtoupper(substr(uniqid(), -5)),
            'items' => $this->cartItems(),
            'subtotal' => $totals['subtotal'],
            'service_fee' => $totals['service_fee'],
            'total' => $totals['total'],
            'payment_method' => $validated['payment_method'],
            'payment_status' => 'pending',
            'status' => $validated['payment_method'] === 'mpesa' ? 'awaiting_mpesa' : 'awaiting_payment_confirmation',
            'mpesa_phone' => $validated['payment_method'] === 'mpesa' ? $validated['phone'] : null,
            'delivery_method' => $validated['delivery_method'],
            'delivery_address' => $validated['address'],
            'notes' => $validated['notes'],
        ]);

        session()->forget('cart');

        if ($order->payment_method === 'mpesa') {
            return redirect()->route('account.orders.mpesa', $order);
        }

        return redirect()->route('account.orders.show', $order)->with('account_success', 'Order created. Follow the payment instructions to complete confirmation.');
    }

    private function sharedData(): array
    {
        return array_merge($this->content->sharedData(), [
            'cartCount' => $this->cartCount(),
        ]);
    }

    private function cartItems(): array
    {
        $sessionCart = session('cart', []);

        if (empty($sessionCart)) {
            return [];
        }

        $slugs = collect($sessionCart)->pluck('slug')->unique()->all();
        $products = Product::with(['category', 'options'])->whereIn('slug', $slugs)->where('is_visible', true)->get()->keyBy('slug');

        return collect($sessionCart)->map(function (array $item, string $key) use ($products) {
            $product = $products->get($item['slug']);

            if (! $product) {
                return null;
            }

            $quantity = (int) $item['quantity'];

            return [
                'key' => $key,
                'product' => [
                    'slug' => $product->slug,
                    'name' => $product->name,
                    'image' => $product->image_url,
                    'category' => $product->category?->name ?? '',
                    'price' => $product->price,
                    'unit' => $product->unit ?? '',
                ],
                'finish' => $item['finish'],
                'quantity' => $quantity,
                'line_total' => $product->price * $quantity,
            ];
        })->filter()->values()->all();
    }

    private function cartTotals(): array
    {
        $subtotal = collect($this->cartItems())->sum('line_total');
        $serviceFee = $subtotal > 0 ? 500 : 0;

        return [
            'subtotal' => $subtotal,
            'service_fee' => $serviceFee,
            'total' => $subtotal + $serviceFee,
        ];
    }

    private function cartCount(): int
    {
        return collect(session('cart', []))->sum('quantity');
    }

    private function workStats(): array
    {
        $settings = $this->content->sharedData()['siteSettings'];
        return $settings['work_stats'] ?? [
            ['value' => '500+', 'label' => 'Projects Completed'],
            ['value' => '50+',  'label' => 'Industry Awards'],
            ['value' => '99%',  'label' => 'Client Satisfaction'],
            ['value' => '10+',  'label' => 'Years Experience'],
        ];
    }

    private function caseStudies(): array
    {
        return \App\Models\PortfolioProject::where('is_case_study', true)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($p) => [
                'client'    => $p->client,
                'title'     => $p->title,
                'image'     => $p->image_url,
                'challenge' => $p->challenge,
                'solution'  => $p->solution,
                'results'   => $p->results ?? [],
            ])
            ->all();
    }
}
