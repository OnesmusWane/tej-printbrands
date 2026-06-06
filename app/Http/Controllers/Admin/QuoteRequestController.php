<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteRequestController extends Controller
{
    public function updateStatus(Request $request, QuoteRequest $quoteRequest): JsonResponse
    {
        // 'quoted' is reserved — only set automatically when a quotation is created
        $data = $request->validate([
            'status' => ['required', 'in:new,rejected'],
        ]);

        $quoteRequest->update($data);

        return response()->json($quoteRequest->fresh());
    }
}
