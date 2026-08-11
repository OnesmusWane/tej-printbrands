<!DOCTYPE html><html><head><meta charset="utf-8"><title>Your Quotation</title></head>
<body style="font-family:sans-serif;max-width:480px;margin:40px auto;padding:20px;color:#1F2937;">
  <div style="text-align:center;margin-bottom:32px;"><div style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;background:#00BCD4;border-radius:12px;"><span style="color:white;font-weight:bold;font-size:20px;">TJ</span></div></div>
  <h2 style="font-size:24px;font-weight:700;margin-bottom:8px;">Your Quotation is Ready</h2>
  <p style="color:#6B7280;margin-bottom:24px;">Hello {{ $quotation->client }}, please find your quotation <strong>{{ $quotation->quote_number }}</strong> attached as a PDF.</p>
  <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;padding:20px;margin-bottom:24px;">
    @if ($quotation->vat_included !== false)
      <p style="margin:0 0 4px;color:#6B7280;">Subtotal: Ksh {{ number_format($quotation->subtotal) }}</p>
      <p style="margin:0 0 10px;color:#6B7280;">VAT (16%): Ksh {{ number_format($quotation->tax) }}</p>
    @endif
    <p style="margin:0 0 6px;"><strong>Total{{ $quotation->vat_included === false ? ' (VAT Exempt)' : '' }}:</strong> Ksh {{ number_format($quotation->total) }}</p>
    @if ($quotation->service)
      <p style="margin:0;color:#6B7280;">{{ $quotation->service }}</p>
    @endif
  </div>
  <p style="font-size:13px;color:#9CA3AF;">If you have any questions about this quotation, just reply to this email.</p>
</body></html>
