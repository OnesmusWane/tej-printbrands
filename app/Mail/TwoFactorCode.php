<?php
namespace App\Mail;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
class TwoFactorCode extends Mailable {
    public function __construct(public string $code, public string $name) {}
    public function envelope(): Envelope { return new Envelope(subject: 'Your Admin Login Code'); }
    public function content(): Content { return new Content(view: 'emails.two-factor-code'); }
}
