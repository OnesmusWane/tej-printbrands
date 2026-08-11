<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\InvoicePdf;
use App\Models\Invoice;
use App\Services\InvoicePaymentService;
use App\Services\InvoicePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class InvoiceController extends Controller
{
    private const ITEM_RULES = [
        'items' => ['sometimes', 'array', 'min:1'],
        'items.*.description' => ['required_with:items', 'string', 'max:255'],
        'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
        'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
    ];

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
        return response()->json($invoice->load('items', 'quotation', 'payments'));
    }

    public function store(Request $request, InvoicePaymentService $service): JsonResponse
    {
        $data = $request->validate([
            'quotation_id' => ['nullable', 'exists:quotations,id'],
            'client' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:160'],
            'service' => ['nullable', 'string', 'max:160'],
            'due_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string'],
            'terms' => ['nullable', 'string', 'max:2000'],
            'vat_included' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:draft,unpaid,partial,paid,overdue'],
            ...self::ITEM_RULES,
        ]);

        $data['status'] ??= 'draft';

        $invoice = $service->createInvoice($data);

        if ($invoice->status !== 'draft') {
            $this->emailInvoicePdf($invoice);
        }

        return response()->json($invoice, 201);
    }

    /**
     * Handles both a plain status/payment-field patch (from the status dropdown or
     * payment-recording flow, which only ever send a subset of {status,
     * payment_method, due_date}) and a full content edit (client/email/items/etc,
     * from the edit form) through the same endpoint.
     */
    public function update(Request $request, Invoice $invoice, InvoicePaymentService $service): JsonResponse
    {
        $data = $request->validate([
            'client' => ['sometimes', 'string', 'max:160'],
            'email' => ['sometimes', 'nullable', 'email', 'max:160'],
            'service' => ['sometimes', 'nullable', 'string', 'max:160'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'payment_method' => ['sometimes', 'nullable', 'string'],
            'terms' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'vat_included' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:draft,unpaid,partial,paid,overdue'],
            ...self::ITEM_RULES,
        ]);

        $wasDraft = $invoice->status === 'draft';

        $invoice = $service->updateInvoice($invoice, $data);

        // Only auto-email when the status is newly transitioning out of 'draft' (the
        // Send button) — a content edit doesn't email on its own; the frontend asks
        // the admin first and, if confirmed, hits the dedicated /send endpoint below.
        if ($wasDraft && ($data['status'] ?? null) && $data['status'] !== 'draft') {
            $this->emailInvoicePdf($invoice);
        }

        return response()->json($invoice);
    }

    /**
     * Generate a fresh PDF and email it to the client — used for the initial "Send"
     * action, resending an already-sent invoice, and sharing an edited version.
     * Works regardless of current status; if no email is on file, the caller must
     * supply one (and it's saved onto the invoice for next time).
     */
    public function send(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'email', 'max:160'],
        ]);

        $email = $validated['email'] ?? $invoice->email;

        if (! $email) {
            return response()->json(['message' => 'An email address is required to send this invoice.'], 422);
        }

        $invoice->update([
            'email' => $email,
            'status' => $invoice->status === 'draft' ? 'unpaid' : $invoice->status,
            'sent_at' => now(),
        ]);

        $this->emailInvoicePdf($invoice);

        return response()->json($invoice->load('items', 'payments'));
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $invoice->delete();

        return response()->json(['message' => 'Deleted']);
    }

    /**
     * Generate the invoice PDF (via DomPDF) and email it to the client. Never blocks
     * the calling request — a delivery failure is logged, not surfaced as an API
     * error, since the invoice itself has already been saved successfully by this
     * point. The generated file is always deleted afterward — every invoice can be
     * regenerated on demand from the database.
     */
    private function emailInvoicePdf(Invoice $invoice): void
    {
        if (! $invoice->email) {
            return;
        }

        $pdfPath = null;

        try {
            $pdfPath = app(InvoicePdfService::class)->generate($invoice);
            Mail::to($invoice->email)->send(new InvoicePdf($invoice, $pdfPath));
        } catch (Throwable $e) {
            Log::error('Failed to email invoice PDF', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        } finally {
            if ($pdfPath && is_file($pdfPath)) {
                @unlink($pdfPath);
            }
        }
    }
}
