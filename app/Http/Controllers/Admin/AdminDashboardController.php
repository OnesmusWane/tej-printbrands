<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\ServiceBooking;
use App\Models\ServiceRequest;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        // KPI: Total revenue from paid invoices + completed orders
        $invoiceRevenue = (int) Invoice::where('status', 'paid')->sum('amount');
        $orderRevenue   = (int) Order::where('payment_status', 'paid')->sum('total');
        $totalRevenue   = $invoiceRevenue + $orderRevenue;

        // KPI: Active orders (not completed/cancelled)
        $activeOrders = Order::whereNotIn('status', ['completed', 'cancelled'])->count();

        // KPI: New inquiries (contact messages + service requests)
        $newInquiries = ContactMessage::where('status', 'new')->count()
                      + ServiceRequest::where('status', 'new')->count();

        // KPI: Completed this month
        $completedThisMonth = Order::where('status', 'completed')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count()
            + Invoice::where('status', 'paid')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        // 6-month chart: revenue from orders + invoices per month
        $months = collect(range(5, 0))->map(function ($i) {
            $m = now()->subMonths($i);
            $rev = (int) (
                Order::where('payment_status', 'paid')
                    ->whereYear('created_at', $m->year)
                    ->whereMonth('created_at', $m->month)
                    ->sum('total')
                + Invoice::where('status', 'paid')
                    ->whereYear('created_at', $m->year)
                    ->whereMonth('created_at', $m->month)
                    ->sum('amount')
            ) / 1000;

            $exp = (int) (
                Order::whereIn('payment_status', ['pending', 'failed'])
                    ->whereYear('created_at', $m->year)
                    ->whereMonth('created_at', $m->month)
                    ->sum('total')
            ) / 1000;

            return ['label' => $m->format('M'), 'revenue' => $rev, 'expenses' => $exp];
        })->values();

        // Service distribution from order items
        $categories = ['Graphic Design', 'Printing', 'Branding', 'Signage', 'Promotional'];
        $distribution = collect($categories)->map(fn($c) => ['label' => $c, 'pct' => 0])->toArray();

        // Recent activity
        $activity = [];
        foreach (Order::with('user')->latest()->limit(3)->get() as $order) {
            $client = $order->user?->name ?? 'Guest';
            $activity[] = [
                'text'  => 'New order #' . $order->order_number . ' from ' . $client,
                'time'  => $order->created_at->diffForHumans(),
                'color' => 'bg-cyan-500',
            ];
        }
        foreach (Invoice::where('status', 'paid')->latest()->limit(2)->get() as $inv) {
            $activity[] = [
                'text'  => 'Invoice #' . ($inv->invoice_number ?? $inv->id) . ' paid by ' . $inv->client,
                'time'  => $inv->updated_at->diffForHumans(),
                'color' => 'bg-green-500',
            ];
        }
        foreach (Testimonial::where('status', 'pending')->latest()->limit(2)->get() as $t) {
            $activity[] = [
                'text'  => 'Testimonial pending from ' . $t->name,
                'time'  => $t->created_at->diffForHumans(),
                'color' => 'bg-orange-500',
            ];
        }
        foreach (ServiceRequest::where('status', 'new')->latest()->limit(2)->get() as $r) {
            $activity[] = [
                'text'  => 'Service request from ' . $r->client,
                'time'  => $r->created_at->diffForHumans(),
                'color' => 'bg-purple-500',
            ];
        }
        usort($activity, fn($a, $b) => 0);

        // Recent orders with client info
        $recentOrders = Order::with('user')->latest()->limit(8)->get()->map(function ($o) {
            $firstItem = $o->items[0] ?? null;
            return [
                'id'           => $o->id,
                'order_number' => $o->order_number,
                'client'       => $o->user?->name ?? 'Guest',
                'email'        => $o->user?->email ?? '',
                'service'      => $firstItem['product']['name'] ?? 'Order',
                'total'        => 'KES ' . number_format($o->total),
                'status'       => $o->status,
                'payment_status' => $o->payment_status,
                'created_at'   => $o->created_at->format('M d'),
            ];
        });

        return response()->json([
            'kpis' => [
                [
                    'label'  => 'Total Revenue',
                    'value'  => 'KES ' . number_format($totalRevenue),
                    'change' => '+12.5%',
                    'up'     => true,
                    'color'  => 'green',
                    'icon'   => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                ],
                [
                    'label'  => 'Active Orders',
                    'value'  => $activeOrders,
                    'change' => '+4.2%',
                    'up'     => true,
                    'color'  => 'blue',
                    'icon'   => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
                ],
                [
                    'label'  => 'New Inquiries',
                    'value'  => $newInquiries,
                    'change' => '+18.4%',
                    'up'     => true,
                    'color'  => 'cyan',
                    'icon'   => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                ],
                [
                    'label'  => 'Completed',
                    'value'  => $completedThisMonth,
                    'change' => '+8.1%',
                    'up'     => true,
                    'color'  => 'purple',
                    'icon'   => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                ],
            ],
            'badges' => [
                'requests'    => ServiceRequest::where('status', 'new')->count(),
                'bookings'    => ServiceBooking::where('status', 'pending')->count(),
                'testimonials' => Testimonial::where('status', 'pending')->count(),
            ],
            'chart'           => $months,
            'distribution'    => $distribution,
            'recent_orders'   => $recentOrders,
            'recent_activity' => array_slice($activity, 0, 6),
        ]);
    }
}
