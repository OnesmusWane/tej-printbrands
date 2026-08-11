@extends('emails.layout')

@section('subject', 'Your Invoice ' . $invoice->invoice_number)

@php
    $hasPayment = $invoice->paid_amount > 0;
@endphp

@section('content')
  <h2 style="margin:0 0 8px;font-size:22px;font-weight:bold;color:#1F2937;">Your Invoice is Ready</h2>
  <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#6B7280;">
    Hello {{ $invoice->client }}, please find invoice
    <strong style="color:#1F2937;">{{ $invoice->invoice_number }}</strong> attached to this email as a PDF.
    @if ($invoice->due_date)
      Payment is due by <strong style="color:#1F2937;">{{ $invoice->due_date->format('d M Y') }}</strong>.
    @endif
  </p>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;margin-bottom:24px;">
    <tr>
      <td style="padding:24px;font-family:Arial,Helvetica,sans-serif;">
        @if ($invoice->service)
          <p style="margin:0 0 16px;font-size:13px;color:#6B7280;">
            <span style="text-transform:uppercase;letter-spacing:0.5px;font-size:11px;color:#9CA3AF;">Service</span><br>
            <span style="font-size:14px;color:#1F2937;font-weight:bold;">{{ $invoice->service }}</span>
          </p>
        @endif

        @if ($invoice->vat_included !== false)
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:10px;">
            <tr>
              <td style="padding:3px 0;font-size:13px;color:#6B7280;">Subtotal</td>
              <td align="right" style="padding:3px 0;font-size:13px;color:#1F2937;">Ksh {{ number_format($invoice->subtotal) }}</td>
            </tr>
            <tr>
              <td style="padding:3px 0 10px;font-size:13px;color:#6B7280;{{ $hasPayment ? '' : 'border-bottom:1px solid #E5E7EB;' }}">VAT (16%)</td>
              <td align="right" style="padding:3px 0 10px;font-size:13px;color:#1F2937;{{ $hasPayment ? '' : 'border-bottom:1px solid #E5E7EB;' }}">Ksh {{ number_format($invoice->tax) }}</td>
            </tr>
          </table>
        @endif

        @if ($hasPayment)
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:10px;">
            <tr>
              <td style="padding:3px 0 10px;font-size:13px;color:#6B7280;border-bottom:1px solid #E5E7EB;">Amount Paid</td>
              <td align="right" style="padding:3px 0 10px;font-size:13px;color:#16a34a;border-bottom:1px solid #E5E7EB;">- Ksh {{ number_format($invoice->paid_amount) }}</td>
            </tr>
          </table>
        @endif

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td style="font-size:16px;font-weight:bold;color:#1F2937;">
              {{ $hasPayment ? 'Balance Due' : 'Amount Due' }}{{ $invoice->vat_included === false ? ' (VAT Exempt)' : '' }}
            </td>
            <td align="right" style="font-size:20px;font-weight:bold;color:#00BCD4;">
              Ksh {{ number_format($invoice->balance_due) }}
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <p style="margin:0;font-size:13px;line-height:1.6;color:#9CA3AF;">
    If you have any questions about this invoice, just reply to this email and we'll be happy to help.
  </p>
@endsection
