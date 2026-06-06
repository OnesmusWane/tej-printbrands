<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceBooking;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ServiceRequest::with('booking')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('client', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('service', 'like', "%{$s}%")
                  ->orWhere('request_number', 'like', "%{$s}%");
            });
        }

        return response()->json($query->paginate((int) $request->input('per_page', 50)));
    }

    public function show(ServiceRequest $serviceRequest): JsonResponse
    {
        return response()->json($serviceRequest->load('booking'));
    }

    public function updateStatus(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $data = $request->validate([
            'status'   => ['required', 'in:new,in_review,quoted,confirmed,completed,rejected'],
            'priority' => ['nullable', 'in:low,medium,high'],
        ]);

        $serviceRequest->update($data);

        return response()->json($serviceRequest->load('booking'));
    }

    public function convertToBooking(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $data = $request->validate([
            'delivery_date'  => ['required', 'date', 'after_or_equal:today'],
            'phone'          => ['nullable', 'string', 'max:40'],
            'location'       => ['nullable', 'string', 'max:160'],
            'price'          => ['nullable', 'integer', 'min:0'],
            'notes'          => ['nullable', 'string', 'max:2000'],
            'preferred_date' => ['nullable', 'date'],
        ]);

        // Prevent duplicate bookings from the same request
        if ($serviceRequest->booking()->exists()) {
            return response()->json(['message' => 'A booking already exists for this request.'], 409);
        }

        $prefix = 'BKG-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -5));

        $booking = ServiceBooking::create([
            'booking_number'     => $prefix,
            'service_request_id' => $serviceRequest->id,
            'client'             => $serviceRequest->client,
            'email'              => $serviceRequest->email,
            'phone'              => $data['phone'] ?? null,
            'service'            => $serviceRequest->service,
            'preferred_date'     => $data['preferred_date'] ?? null,
            'delivery_date'      => $data['delivery_date'],
            'location'           => $data['location'] ?? null,
            'budget'             => $serviceRequest->budget,
            'project_description' => $serviceRequest->description,
            'notes'              => $data['notes'] ?? null,
            'price'              => $data['price'] ?? null,
            'status'             => 'confirmed',
        ]);

        $serviceRequest->update(['status' => 'confirmed']);

        return response()->json([
            'booking'         => $booking,
            'service_request' => $serviceRequest->fresh()->load('booking'),
        ], 201);
    }
}
