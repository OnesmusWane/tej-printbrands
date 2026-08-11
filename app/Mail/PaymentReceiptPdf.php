<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaymentReceiptPdf extends Mailable
{
    public function __construct(public Payment $payment, public string $pdfPath)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Payment Receipt {$this->payment->payment_number}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-receipt', with: ['payment' => $this->payment]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as("Receipt-{$this->payment->payment_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
