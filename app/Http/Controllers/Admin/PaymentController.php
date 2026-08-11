<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePaymentRequest;
use App\Mail\PaymentReceiptPdf;
use App\Models\Payment;
use App\Services\InvoicePaymentService;
use App\Services\PaymentReceiptPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

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

    /**
     * Record a payment and immediately email the receipt to the client — every
     * payment is "made" the moment it's recorded, so unlike invoices/quotations
     * there's no draft state to gate the send on.
     */
    public function store(StorePaymentRequest $request, InvoicePaymentService $service): JsonResponse
    {
        $payment = $service->recordPayment($request->validated());

        $this->emailReceiptPdf($payment);

        return response()->json($payment->load('invoice'), 201);
    }

    /**
     * Edit a payment's content. Never auto-emails — the frontend asks the admin
     * first and, if confirmed, hits the dedicated /send endpoint below.
     */
    public function update(Request $request, Payment $payment, InvoicePaymentService $service): JsonResponse
    {
        $data = $request->validate([
            'invoice_id' => ['sometimes', 'nullable', 'exists:invoices,id'],
            'client' => ['sometimes', 'string', 'max:160'],
            'email' => ['sometimes', 'nullable', 'email', 'max:160'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'method' => ['sometimes', 'in:cash,mpesa,bank_transfer'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:120'],
            'paid_at' => ['sometimes', 'date'],
            'status' => ['sometimes', 'in:pending,completed,refunded,failed'],
        ]);

        $payment = $service->updatePayment($payment, $data);

        return response()->json($payment);
    }

    /**
     * Generate a fresh receipt PDF and email it to the client — used for the
     * initial auto-send and for resending/reshared an already-sent receipt.
     * If no email is on file, the caller must supply one (and it's saved onto
     * the payment for next time).
     */
    public function send(Request $request, Payment $payment): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'email', 'max:160'],
        ]);

        $email = $validated['email'] ?? $payment->email;

        if (! $email) {
            return response()->json(['message' => 'An email address is required to send this receipt.'], 422);
        }

        $payment->update(['email' => $email, 'sent_at' => now()]);

        $this->emailReceiptPdf($payment);

        return response()->json($payment->load('invoice'));
    }

    /**
     * Delete a payment, rolling its amount back off the linked invoice's
     * paid_amount/status (if any) so totals never drift out of sync.
     */
    public function destroy(Payment $payment, InvoicePaymentService $service): JsonResponse
    {
        $service->deletePayment($payment);

        return response()->json(['message' => 'Deleted']);
    }

    /**
     * Generate the receipt PDF (via DomPDF) and email it to the client. Never
     * blocks the calling request — a delivery failure is logged, not surfaced as
     * an API error, since the payment itself has already been saved successfully
     * by this point. The generated file is always deleted afterward — every
     * receipt can be regenerated on demand from the database.
     */
    private function emailReceiptPdf(Payment $payment): void
    {
        if (! $payment->email) {
            return;
        }

        $pdfPath = null;

        try {
            $pdfPath = app(PaymentReceiptPdfService::class)->generate($payment);
            Mail::to($payment->email)->send(new PaymentReceiptPdf($payment, $pdfPath));
        } catch (Throwable $e) {
            Log::error('Failed to email payment receipt PDF', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        } finally {
            if ($pdfPath && is_file($pdfPath)) {
                @unlink($pdfPath);
            }
        }
    }
}
