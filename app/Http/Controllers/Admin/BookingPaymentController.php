<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\ServiceBooking;
use App\Services\InvoicePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingPaymentController extends Controller
{
    public function store(Request $request, ServiceBooking $booking, InvoicePaymentService $service): JsonResponse
    {
        $data = $request->validate([
            'method'    => ['required', 'in:cash,mpesa,bank_transfer'],
            'amount'    => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:120'],
            'phone'     => ['nullable', 'string', 'max:20'],
        ]);

        // Create or reuse the invoice for this booking
        $invoiceNumber = 'INV-BK-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT);
        $invoice = Invoice::firstOrCreate(
            ['invoice_number' => $invoiceNumber],
            [
                'client'   => $booking->client,
                'email'    => $booking->email ?? '',
                'amount'   => $data['amount'],
                'status'   => 'unpaid',
                'due_date' => now()->toDateString(),
            ]
        );

        // If invoice already exists but amount changed (partial), update amount
        if ($invoice->amount < $data['amount']) {
            $invoice->update(['amount' => $data['amount']]);
        }

        $ref = $data['reference'] ?? $data['phone'] ?? null;

        $payment = $service->recordPayment([
            'invoice_id' => $invoice->id,
            'client'     => $booking->client,
            'amount'     => $data['amount'],
            'method'     => $data['method'],
            'reference'  => $ref,
        ]);

        // Update booking status to confirmed + store price
        $booking->update([
            'status' => 'confirmed',
            'price'  => $data['amount'],
        ]);

        return response()->json([
            'payment' => $payment,
            'invoice' => $invoice->fresh(),
            'booking' => $booking->fresh(),
        ], 201);
    }
}
