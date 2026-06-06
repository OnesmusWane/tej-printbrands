<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Services\InvoicePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with('quotation')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('client', 'like', "%{$s}%")
                  ->orWhere('invoice_number', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        return response()->json($query->paginate((int) $request->input('per_page', 50)));
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json($invoice->load('quotation.items', 'payments'));
    }

    public function store(Request $request, InvoicePaymentService $service): JsonResponse
    {
        $data = $request->validate([
            'quotation_id' => ['nullable', 'exists:quotations,id'],
            'client'       => ['required', 'string', 'max:160'],
            'email'        => ['nullable', 'email', 'max:160'],
            'amount'       => ['required', 'integer', 'min:1'],
            'due_date'     => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string'],
            'notes'        => ['nullable', 'string'],
        ]);

        if (!empty($data['quotation_id'])) {
            $quotation = Quotation::findOrFail($data['quotation_id']);
            $data['client'] = $quotation->client;
            $data['email']  = $quotation->email;
            $data['amount'] = $quotation->total;
        }

        $invoice = $service->createInvoice($data);

        return response()->json($invoice, 201);
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $data = $request->validate([
            'status'         => ['sometimes', 'in:unpaid,partial,paid,overdue'],
            'payment_method' => ['sometimes', 'string'],
            'due_date'       => ['sometimes', 'nullable', 'date'],
        ]);

        $invoice->update($data);

        return response()->json($invoice->fresh());
    }
}
