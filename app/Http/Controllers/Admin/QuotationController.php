<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuotationRequest;
use App\Mail\QuotationPdf;
use App\Models\QuoteRequest;
use App\Models\Quotation;
use App\Services\QuotationPdfService;
use App\Services\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class QuotationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Quotation::with('items')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('client', 'like', "%{$s}%")
                  ->orWhere('quote_number', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        return response()->json($query->paginate((int) $request->input('per_page', 50)));
    }

    public function show(Quotation $quotation): JsonResponse
    {
        return response()->json($quotation->load('items'));
    }

    public function store(StoreQuotationRequest $request, QuotationService $service): JsonResponse
    {
        $quotation = $service->create($request->validated());

        // Auto-mark the source quote request as 'quoted'
        if ($request->filled('quote_request_id')) {
            QuoteRequest::where('id', $request->quote_request_id)
                ->whereNot('status', 'quoted')
                ->update(['status' => 'quoted']);
        }

        // A quotation created directly as 'pending' is being sent to the client now.
        if ($quotation->status === 'pending') {
            $this->emailQuotationPdf($quotation);
        }

        return response()->json($quotation, 201);
    }

    /**
     * Handles both a plain status transition (from the Approve/Reject/Send buttons,
     * which only ever send {status}) and a full content edit (client/email/items/etc,
     * from the edit form) through the same endpoint.
     */
    public function update(Request $request, Quotation $quotation, QuotationService $service): JsonResponse
    {
        $validated = $request->validate([
            'client' => ['sometimes', 'string', 'max:160'],
            'email' => ['sometimes', 'email', 'max:160'],
            'service' => ['sometimes', 'nullable', 'string', 'max:160'],
            'terms' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'vat_included' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:draft,pending,approved,rejected'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.description' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
        ]);

        $wasPending = $quotation->status === 'pending';

        $quotation = $service->update($quotation, $validated);

        // Only auto-email when the status is newly transitioning to 'pending' (the
        // Send button) — a content edit doesn't email on its own; the frontend asks
        // the admin first and, if confirmed, hits the dedicated /send endpoint below.
        if (($validated['status'] ?? null) === 'pending' && ! $wasPending) {
            $this->emailQuotationPdf($quotation);
        }

        return response()->json($quotation);
    }

    /**
     * Generate a fresh PDF and email it to the client — used for the initial "Send"
     * action, resending an already-sent quotation, and sharing an edited version.
     * Works regardless of current status; if no email is on file, the caller must
     * supply one (and it's saved onto the quotation for next time).
     */
    public function send(Request $request, Quotation $quotation): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'email', 'max:160'],
        ]);

        $email = $validated['email'] ?? $quotation->email;

        if (! $email) {
            return response()->json(['message' => 'An email address is required to send this quotation.'], 422);
        }

        $quotation->update([
            'email' => $email,
            'status' => $quotation->status === 'draft' ? 'pending' : $quotation->status,
            'sent_at' => now(),
        ]);

        $this->emailQuotationPdf($quotation);

        return response()->json($quotation->load('items'));
    }

    public function destroy(Quotation $quotation): JsonResponse
    {
        $quotation->delete();

        return response()->json(['message' => 'Deleted']);
    }

    /**
     * Generate the quotation PDF (via DomPDF, so shared hosting never needs a local
     * headless browser) and email it to the client. Never blocks the calling request —
     * a delivery failure is logged, not surfaced as an API error, since the quotation
     * itself has already been saved successfully by this point.
     *
     * The generated file is always deleted afterward — every quotation can be
     * regenerated on demand from the database, so nothing is lost, and there's no
     * reason to let PDFs accumulate on a disk-quota-constrained host.
     */
    private function emailQuotationPdf(Quotation $quotation): void
    {
        if (! $quotation->email) {
            return;
        }

        $pdfPath = null;

        try {
            $pdfPath = app(QuotationPdfService::class)->generate($quotation);
            Mail::to($quotation->email)->send(new QuotationPdf($quotation, $pdfPath));
        } catch (Throwable $e) {
            Log::error('Failed to email quotation PDF', [
                'quotation_id' => $quotation->id,
                'error' => $e->getMessage(),
            ]);
        } finally {
            if ($pdfPath && is_file($pdfPath)) {
                @unlink($pdfPath);
            }
        }
    }
}
