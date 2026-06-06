<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceBooking;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client'         => ['required', 'string', 'max:160'],
            'email'          => ['required', 'email', 'max:160'],
            'phone'          => ['nullable', 'string', 'max:40'],
            'service'        => ['required', 'string', 'max:160'],
            'preferred_date' => ['nullable', 'date'],
            'delivery_date'  => ['nullable', 'date'],
            'location'       => ['nullable', 'string', 'max:160'],
            'price'          => ['nullable', 'integer', 'min:0'],
            'notes'          => ['nullable', 'string', 'max:2000'],
            'description'    => ['nullable', 'string', 'max:2000'],
        ]);

        // Auto-create a service request (confirmed) to preserve full audit trail
        $serviceRequest = ServiceRequest::create([
            'request_number' => 'REQ-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
            'client'         => $data['client'],
            'email'          => $data['email'],
            'phone'          => $data['phone'] ?? null,
            'service'        => $data['service'],
            'description'    => $data['description'] ?? $data['notes'] ?? null,
            'priority'       => 'medium',
            'status'         => 'confirmed',
        ]);

        $booking = ServiceBooking::create([
            'booking_number'     => 'BKG-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
            'service_request_id' => $serviceRequest->id,
            'client'             => $data['client'],
            'email'              => $data['email'],
            'phone'              => $data['phone'] ?? null,
            'service'            => $data['service'],
            'preferred_date'     => $data['preferred_date'] ?? null,
            'delivery_date'      => $data['delivery_date'] ?? null,
            'location'           => $data['location'] ?? null,
            'price'              => $data['price'] ?? null,
            'notes'              => $data['notes'] ?? null,
            'status'             => 'confirmed',
        ]);

        return response()->json($booking->load('serviceRequest'), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $query = ServiceBooking::with('serviceRequest')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate((int) $request->input('per_page', 50)));
    }

    public function update(Request $request, ServiceBooking $serviceBooking): JsonResponse
    {
        $data = $request->validate([
            'status'        => ['sometimes', 'in:pending,confirmed,cancelled,completed'],
            'delivery_date' => ['sometimes', 'nullable', 'date'],
            'price'         => ['sometimes', 'nullable', 'integer', 'min:0'],
            'notes'         => ['sometimes', 'nullable', 'string'],
        ]);

        $serviceBooking->update($data);

        return response()->json($serviceBooking->fresh());
    }
}
