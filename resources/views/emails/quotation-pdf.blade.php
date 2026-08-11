@extends('emails.layout')

@section('subject', 'Your Quotation ' . $quotation->quote_number)

@section('content')
  <h2 style="margin:0 0 8px;font-size:22px;font-weight:bold;color:#1F2937;">Your Quotation is Ready</h2>
  <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#6B7280;">
    Hello {{ $quotation->client }}, thank you for your interest. Please find quotation
    <strong style="color:#1F2937;">{{ $quotation->quote_number }}</strong> attached to this email as a PDF.
  </p>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;margin-bottom:24px;">
    <tr>
      <td style="padding:24px;font-family:Arial,Helvetica,sans-serif;">
        @if ($quotation->service)
          <p style="margin:0 0 16px;font-size:13px;color:#6B7280;">
            <span style="text-transform:uppercase;letter-spacing:0.5px;font-size:11px;color:#9CA3AF;">Service</span><br>
            <span style="font-size:14px;color:#1F2937;font-weight:bold;">{{ $quotation->service }}</span>
          </p>
        @endif

        @if ($quotation->vat_included !== false)
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:10px;">
            <tr>
              <td style="padding:3px 0;font-size:13px;color:#6B7280;">Subtotal</td>
              <td align="right" style="padding:3px 0;font-size:13px;color:#1F2937;">Ksh {{ number_format($quotation->subtotal) }}</td>
            </tr>
            <tr>
              <td style="padding:3px 0 10px;font-size:13px;color:#6B7280;border-bottom:1px solid #E5E7EB;">VAT (16%)</td>
              <td align="right" style="padding:3px 0 10px;font-size:13px;color:#1F2937;border-bottom:1px solid #E5E7EB;">Ksh {{ number_format($quotation->tax) }}</td>
            </tr>
          </table>
        @endif

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td style="padding-top:{{ $quotation->vat_included !== false ? '10' : '0' }}px;font-size:16px;font-weight:bold;color:#1F2937;">
              Total{{ $quotation->vat_included === false ? ' (VAT Exempt)' : '' }}
            </td>
            <td align="right" style="padding-top:{{ $quotation->vat_included !== false ? '10' : '0' }}px;font-size:20px;font-weight:bold;color:#00BCD4;">
              Ksh {{ number_format($quotation->total) }}
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <p style="margin:0;font-size:13px;line-height:1.6;color:#9CA3AF;">
    If you have any questions about this quotation, just reply to this email and we'll be happy to help.
  </p>
@endsection
