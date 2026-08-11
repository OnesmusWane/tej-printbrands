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

    public function update(Request $request, Quotation $quotation): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:draft,pending,approved,rejected'],
        ]);

        $wasPending = $quotation->status === 'pending';
        $quotation->update($data);

        // Only email when the status is newly transitioning to 'pending' — not on every save.
        if ($quotation->status === 'pending' && ! $wasPending) {
            $this->emailQuotationPdf($quotation);
        }

        return response()->json($quotation->load('items'));
    }

    public function send(Quotation $quotation): JsonResponse
    {
        $quotation->update(['status' => 'pending', 'sent_at' => now()]);
        $this->emailQuotationPdf($quotation);

        return response()->json($quotation);
    }

    public function destroy(Quotation $quotation): JsonResponse
    {
        $quotation->delete();

        return response()->json(['message' => 'Deleted']);
    }

    /**
     * Generate the quotation PDF (via headless Chrome, so it matches the on-screen
     * design exactly) and email it to the client. Never blocks the calling request —
     * a delivery failure is logged, not surfaced as an API error, since the quotation
     * itself has already been saved successfully by this point.
     */
    private function emailQuotationPdf(Quotation $quotation): void
    {
        if (! $quotation->email) {
            return;
        }

        try {
            $pdfPath = app(QuotationPdfService::class)->generate($quotation);
            Mail::to($quotation->email)->send(new QuotationPdf($quotation, $pdfPath));
        } catch (Throwable $e) {
            Log::error('Failed to email quotation PDF', [
                'quotation_id' => $quotation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
