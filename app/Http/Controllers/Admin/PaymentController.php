<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePaymentRequest;
use App\Models\Payment;
use App\Services\InvoicePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payment::with('invoice')->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('client', 'like', "%{$s}%")
                  ->orWhere('payment_number', 'like', "%{$s}%")
                  ->orWhere('reference', 'like', "%{$s}%");
            });
        }

        return response()->json($query->paginate((int) $request->input('per_page', 100)));
    }

    public function store(StorePaymentRequest $request, InvoicePaymentService $service): JsonResponse
    {
        return response()->json($service->recordPayment($request->validated()), 201);
    }

    public function update(Request $request, Payment $payment): JsonResponse
    {
        $data = $request->validate([
            'status'    => ['required', 'in:pending,completed,refunded,failed'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:120'],
        ]);

        $payment->update($data);

        return response()->json($payment->fresh()->load('invoice'));
    }
}
