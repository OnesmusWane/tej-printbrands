<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\QuoteRequest;
use App\Models\ServiceBooking;
use App\Models\ServiceRequest;

class PublicSubmissionService
{
    public function createContactMessage(array $data): ContactMessage
    {
        $data['name'] = trim($data['first_name'].' '.($data['last_name'] ?? ''));

        return ContactMessage::create($data);
    }

    public function createQuoteRequest(array $data): QuoteRequest
    {
        $data['request_number'] = $this->number('QR');

        return QuoteRequest::create($data);
    }

    public function createServiceBooking(array $data): ServiceBooking
    {
        $data['booking_number'] = $this->number('BKG');
        $data['client'] = $data['name'] ?? $data['client'];
        unset($data['name']);

        return ServiceBooking::create($data);
    }

    public function createServiceRequest(array $data): ServiceRequest
    {
        $data['request_number'] = $this->number('REQ');

        return ServiceRequest::create($data);
    }

    private function number(string $prefix): string
    {
        return $prefix.'-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -5));
    }
}
