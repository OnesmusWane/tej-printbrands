<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;

class InvoicePaymentService
{
    public function recordPayment(array $data): Payment
    {
        $data['payment_number'] ??= 'PAY-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -4));
        $data['paid_at'] ??= now();

        $payment = Payment::create($data);

        if ($payment->invoice) {
            $invoice = $payment->invoice;
            $invoice->paid_amount += $payment->amount;
            $invoice->payment_method = $payment->method;
            $invoice->status = $invoice->paid_amount >= $invoice->amount ? 'paid' : 'partial';
            $invoice->save();
        }

        return $payment;
    }

    public function createInvoice(array $data): Invoice
    {
        $data['invoice_number'] ??= 'INV-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -4));

        return Invoice::create($data);
    }
}
