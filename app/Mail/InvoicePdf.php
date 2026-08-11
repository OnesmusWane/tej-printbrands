<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class InvoicePdf extends Mailable
{
    public function __construct(public Invoice $invoice, public string $pdfPath)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your Invoice {$this->invoice->invoice_number}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invoice-pdf', with: ['invoice' => $this->invoice]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as("Invoice-{$this->invoice->invoice_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
