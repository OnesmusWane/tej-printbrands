<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class QuotationPdf extends Mailable
{
    public function __construct(public Quotation $quotation, public string $pdfPath)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your Quotation {$this->quotation->quote_number}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.quotation-pdf', with: ['quotation' => $this->quotation]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as("Quotation-{$this->quotation->quote_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
