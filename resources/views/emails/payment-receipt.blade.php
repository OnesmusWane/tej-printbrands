@extends('emails.layout')

@section('subject', 'Payment Receipt ' . $payment->payment_number)

@section('content')
  <h2 style="margin:0 0 8px;font-size:22px;font-weight:bold;color:#1F2937;">Payment Received — Thank You</h2>
  <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#6B7280;">
    Hello {{ $payment->client }}, we've received your payment. Receipt
    <strong style="color:#1F2937;">{{ $payment->payment_number }}</strong> is attached to this email as a PDF.
  </p>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;margin-bottom:24px;">
    <tr>
      <td style="padding:24px;font-family:Arial,Helvetica,sans-serif;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:10px;">
          <tr>
            <td style="padding:3px 0;font-size:13px;color:#6B7280;">Date Paid</td>
            <td align="right" style="padding:3px 0;font-size:13px;color:#1F2937;">{{ ($payment->paid_at ?? $payment->created_at)->format('d M Y') }}</td>
          </tr>
          <tr>
            <td style="padding:3px 0;font-size:13px;color:#6B7280;">Payment Method</td>
            <td align="right" style="padding:3px 0;font-size:13px;color:#1F2937;">{{ ucwords(str_replace('_', ' ', $payment->method)) }}</td>
          </tr>
          @if ($payment->reference)
            <tr>
              <td style="padding:3px 0;font-size:13px;color:#6B7280;">Reference</td>
              <td align="right" style="padding:3px 0;font-size:13px;color:#1F2937;">{{ $payment->reference }}</td>
            </tr>
          @endif
          @if ($payment->invoice)
            <tr>
              <td style="padding:3px 0 10px;font-size:13px;color:#6B7280;border-bottom:1px solid #E5E7EB;">Invoice</td>
              <td align="right" style="padding:3px 0 10px;font-size:13px;color:#1F2937;border-bottom:1px solid #E5E7EB;">{{ $payment->invoice->invoice_number }}</td>
            </tr>
          @endif
        </table>

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td style="font-size:16px;font-weight:bold;color:#1F2937;">Amount Paid</td>
            <td align="right" style="font-size:20px;font-weight:bold;color:#00BCD4;">Ksh {{ number_format($payment->amount, 2) }}</td>
          </tr>
        </table>

        @if ($payment->invoice && $payment->invoice->balance_due > 0)
          <p style="margin:12px 0 0;font-size:12px;color:#9CA3AF;">
            Remaining balance on invoice {{ $payment->invoice->invoice_number }}: Ksh {{ number_format($payment->invoice->balance_due, 2) }}
          </p>
        @endif
      </td>
    </tr>
  </table>

  <p style="margin:0;font-size:13px;line-height:1.6;color:#9CA3AF;">
    If you have any questions about this payment, just reply to this email and we'll be happy to help.
  </p>
@endsection
