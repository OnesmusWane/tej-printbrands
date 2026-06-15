<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_name'      => ['required', 'string', 'max:120'],
            'client_email'     => ['nullable', 'email', 'max:160'],
            'client_phone'     => ['nullable', 'string', 'max:40'],
            'items'            => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.note'       => ['nullable', 'string'],
            'service_fee'      => ['nullable', 'numeric', 'min:0'],
            'payment_method'   => ['required', 'in:mpesa,cash,bank_transfer,card'],
            'delivery_method'  => ['required', 'in:delivery,pickup'],
            'delivery_address' => ['nullable', 'string'],
            'status'           => ['required', 'in:pending,processing,completed,cancelled'],
            'payment_status'   => ['required', 'in:pending,paid,failed,refunded'],
            'notes'            => ['nullable', 'string'],
        ]);

        // Find or create user by email
        $user = null;
        if (!empty($data['client_email'])) {
            $user = User::firstOrCreate(
                ['email' => $data['client_email']],
                ['name' => $data['client_name'], 'phone' => $data['client_phone'] ?? null, 'password' => bcrypt(\Str::random(16))]
            );
        }

        // Build items array — validate stock first
        $items    = [];
        $subtotal = 0;
        $products = [];
        foreach ($data['items'] as $item) {
            $product   = Product::findOrFail($item['product_id']);
            $qty       = (int) $item['quantity'];

            if ($product->stock_quantity !== null && $product->stock_quantity < $qty) {
                return response()->json([
                    'message' => "Insufficient stock for \"{$product->name}\". Available: {$product->stock_quantity}.",
                ], 422);
            }

            $unitPrice = isset($item['unit_price']) && $item['unit_price'] > 0
                ? (float) $item['unit_price']
                : (float) $product->price;
            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;

            $items[] = [
                'key'       => $product->slug,
                'product'   => [
                    'name'     => $product->name,
                    'slug'     => $product->slug,
                    'price'    => $unitPrice,
                    'category' => optional($product->category)->name ?? '',
                    'image'    => $product->image_url ?? '',
                    'unit'     => $product->unit ?? '',
                ],
                'quantity'   => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'note'       => $item['note'] ?? null,
            ];
            $products[] = ['model' => $product, 'qty' => $qty];
        }

        $serviceFee   = (int) ($data['service_fee'] ?? 0);
        $orderNumber  = 'TPB-' . now()->format('ymd') . '-' . strtoupper(\Str::random(5));

        $order = Order::create([
            'user_id'          => $user?->id,
            'order_number'     => $orderNumber,
            'items'            => $items,
            'subtotal'         => (int) $subtotal,
            'service_fee'      => $serviceFee,
            'total'            => (int) $subtotal + $serviceFee,
            'payment_method'   => $data['payment_method'],
            'payment_status'   => $data['payment_status'],
            'status'           => $data['status'],
            'delivery_method'  => $data['delivery_method'],
            'delivery_address' => $data['delivery_address'] ?? null,
            'notes'            => $data['notes'] ?? null,
        ]);

        // Reduce stock after order is persisted
        foreach ($products as ['model' => $product, 'qty' => $qty]) {
            $product->reduceStock($qty);
        }

        return response()->json($order->load('user'), 201);
    }

    public function products(): JsonResponse
    {
        return response()->json(
            Product::where('is_visible', true)->orderBy('name')->get(['id', 'name', 'price', 'unit', 'slug', 'stock_quantity'])
        );
    }

    public function clients(): JsonResponse
    {
        return response()->json(
            User::where('is_admin', false)->orderBy('name')->get(['id', 'name', 'email', 'phone'])
        );
    }
}
