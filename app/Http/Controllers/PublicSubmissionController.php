<?php

namespace App\Http\Controllers;

use App\Http\Requests\Public\StoreContactMessageRequest;
use App\Http\Requests\Public\StoreQuoteRequestRequest;
use App\Http\Requests\Public\StoreServiceBookingRequest;
use App\Services\PublicSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PublicSubmissionController extends Controller
{
    public function __construct(private PublicSubmissionService $submissions)
    {
    }

    public function contact(StoreContactMessageRequest $request): RedirectResponse|JsonResponse
    {
        $message = $this->submissions->createContactMessage($request->validated());

        return $this->response($request, $message, 'Your message has been sent. Our team will reply shortly.');
    }

    public function quote(StoreQuoteRequestRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('artwork')) {
            $data['artwork_path'] = $request->file('artwork')->store('quote-artwork', 'public');
        }
        unset($data['artwork']);

        $quote = $this->submissions->createQuoteRequest($data);

        return $this->response($request, $quote, 'Your quote request has been received.');
    }

    public function booking(StoreServiceBookingRequest $request): RedirectResponse|JsonResponse
    {
        $booking = $this->submissions->createServiceBooking($request->validated());

        return $this->response($request, $booking, 'Your service booking request has been received.');
    }

    private function response($request, $model, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'data' => $model], 201);
        }

        return back()->with('form_success', $message);
    }
}
