<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuotationRequest;
use App\Models\QuoteRequest;
use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return response()->json($quotation, 201);
    }

    public function update(Request $request, Quotation $quotation): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:draft,pending,approved,rejected'],
        ]);
        $quotation->update($data);

        return response()->json($quotation->load('items'));
    }

    public function send(Quotation $quotation): JsonResponse
    {
        $quotation->update(['status' => 'pending', 'sent_at' => now()]);

        return response()->json($quotation);
    }

    public function destroy(Quotation $quotation): JsonResponse
    {
        $quotation->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
