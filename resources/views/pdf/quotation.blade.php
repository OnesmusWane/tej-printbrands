@php
    $company = $settings['company'] ?? [];
    $contact = $settings['contact'] ?? [];
    $business = $settings['business'] ?? [];

    $companyName = $company['name'] ?? $company['company_name'] ?? 'Tej Printbrands';
    $logoUrl = $company['logo_url'] ?? '';
    $address = $contact['address'] ?? 'P.O. BOX 4052-00100, Nairobi';
    $phone = $contact['phone'] ?? '';
    $phoneSecondary = $contact['phone_secondary'] ?? '';
    $email = $contact['email'] ?? '';
    $paybill = $business['mpesa_shortcode'] ?? '';
    $paybillAcct = $business['paybill_account'] ?? '';

    $addrParts = array_values(array_filter(array_map('trim', explode(',', $address))));
    $addr1 = $addrParts[0] ?? '';
    $addr2 = implode(', ', array_slice($addrParts, 1));
    $contactLine = implode(' / ', array_filter([$phone, $phoneSecondary])) ?: $email;

    $items = $q->items ?? collect();
    $blankRowsNeeded = max(0, 10 - $items->count());
@endphp
<!DOCTYPE html>
<html><head><meta charset="utf-8">
<meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
<title>Quotation {{ $q->quote_number }}</title>
<style>
@page{size:A4 portrait;margin:0}
*{box-sizing:border-box;margin:0;padding:0;-webkit-print-color-adjust:exact;print-color-adjust:exact;color-adjust:exact;}
body{font-family:Arial,Helvetica,sans-serif;color:#1F2937;background:#fff}
table{width:100%;border-collapse:collapse}
</style>
</head><body>
<div style="border:4px solid #111;min-height:100vh;position:relative;overflow:hidden;">

<!-- ═══ HEADER: cyan swoosh on top, red drop-shadow trailing beneath it | white (right) ═══ -->
<div style="position:relative;height:210px;overflow:hidden;background:#fff;">
  <svg viewBox="0 0 794 210" width="100%" height="210" preserveAspectRatio="none" style="position:absolute;inset:0;">
    <path d="M0,0 L640,0 C 480,10 500,90 380,130 C 300,158 200,190 150,210 L0,210 Z"
          fill="#00BCD4" style="filter:drop-shadow(11px 9px 3px #F44336);"/>
  </svg>

  <!-- Logo + company (over the cyan curve, left) -->
  <div style="position:absolute;left:26px;top:24px;display:flex;align-items:center;gap:16px;z-index:3;">
    <div style="width:82px;height:82px;border-radius:50%;background:#fff;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.2);">
      @if ($logoUrl)
        <img src="{{ $logoUrl }}" style="width:74px;height:74px;object-fit:contain;" alt="">
      @else
        <span style="font-size:28px;font-weight:900;color:#1a237e;">T</span>
      @endif
    </div>
    <div style="color:#fff;">
      <div style="font-size:16px;font-weight:800;letter-spacing:1px;">{{ strtoupper($companyName) }}</div>
      <div style="font-size:11px;opacity:.95;margin-top:7px;line-height:1.6;">{{ $addr1 }}@if($addr2)<br>{{ $addr2 }}@endif</div>
    </div>
  </div>

  <!-- QUOTATION + date/quote/customer table (right, anchored toward the bottom of the header) -->
  <div style="position:absolute;right:30px;bottom:14px;width:300px;z-index:3;">
    <div style="font-size:32px;font-weight:800;color:#1F2937;letter-spacing:2px;margin-bottom:10px;text-align:right;">QUOTATION</div>
    <table style="width:100%;font-size:11px;border-collapse:collapse;">
      <tr><td style="border:1px solid #999;padding:5px 10px;width:90px;">Date:</td><td style="border:1px solid #999;padding:5px 10px;">{{ $q->created_at->format('d M Y') }}</td></tr>
      <tr><td style="border:1px solid #999;padding:5px 10px;border-top:none;">Quote:</td><td style="border:1px solid #999;padding:5px 10px;border-top:none;">{{ $q->quote_number }}</td></tr>
      <tr><td style="border:1px solid #999;padding:5px 10px;border-top:none;">Customer ID:</td><td style="border:1px solid #999;padding:5px 10px;border-top:none;">{{ $q->client }}</td></tr>
    </table>
  </div>
</div>

<!-- ═══ ITEMS TABLE ═══ -->
<div style="padding:26px 26px 8px;">
  <table>
    <thead>
      <tr style="background:#111;color:#fff;">
        <th style="padding:10px 14px;text-align:left;font-size:12px;letter-spacing:.5px;">ITEM</th>
        <th style="padding:10px 14px;text-align:center;font-size:12px;letter-spacing:.5px;">QTY</th>
        <th style="padding:10px 8px;text-align:right;font-size:11px;line-height:1.3;">UNIT<br>PRICE</th>
        <th style="padding:10px 14px;text-align:right;font-size:12px;letter-spacing:.5px;">TOTAL</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($items as $i)
        <tr>
          <td style="padding:9px 14px;border-bottom:1px solid #00BCD4;">{{ $i->description }}</td>
          <td style="padding:9px 14px;border-bottom:1px solid #00BCD4;text-align:center;">{{ $i->quantity }}</td>
          <td style="padding:9px 8px;border-bottom:1px solid #00BCD4;text-align:right;">Ksh {{ number_format($i->unit_price) }}</td>
          <td style="padding:9px 14px;border-bottom:1px solid #00BCD4;text-align:right;font-weight:600;">Ksh {{ number_format($i->total) }}</td>
        </tr>
      @endforeach
      @for ($r = 0; $r < $blankRowsNeeded; $r++)
        <tr><td colspan="4" style="height:26px;border-bottom:1px solid #00BCD4;"></td></tr>
      @endfor
    </tbody>
  </table>

  <!-- Subtotal + VAT breakdown (only when VAT applies) -->
  @if ($q->vat_included !== false)
    <div style="display:flex;justify-content:flex-end;padding-right:8px;margin-top:8px;">
      <table style="width:48%;">
        <tr><td style="padding:4px 16px;font-size:12px;color:#6B7280;">Subtotal</td><td style="padding:4px 16px;font-size:12px;text-align:right;color:#1F2937;">Ksh {{ number_format($q->subtotal) }}</td></tr>
        <tr><td style="padding:4px 16px;font-size:12px;color:#6B7280;border-bottom:1px solid #e5e7eb;">VAT (16%)</td><td style="padding:4px 16px;font-size:12px;text-align:right;color:#1F2937;border-bottom:1px solid #e5e7eb;">Ksh {{ number_format($q->tax) }}</td></tr>
      </table>
    </div>
  @endif

  <!-- TOTAL bar -->
  <div style="display:flex;justify-content:flex-end;margin-top:{{ $q->vat_included !== false ? '6' : '10' }}px;padding-right:8px;">
    <div style="width:48%;background:#111;color:#fff;font-weight:700;font-size:14px;padding:10px 16px;display:flex;justify-content:space-between;">
      <span>TOTAL{{ $q->vat_included === false ? ' (VAT Exempt)' : '' }}</span>
      <span>Ksh {{ number_format($q->total) }}</span>
    </div>
  </div>

  @if ($q->terms)
    <div style="margin-top:14px;font-size:11px;color:#6B7280;padding-top:10px;border-top:1px solid #e5e7eb;"><strong>Terms &amp; Conditions:</strong> {{ $q->terms }}</div>
  @endif
</div>

<!-- ═══ PAYMENT INFO ═══ -->
<div style="padding:20px 26px 0;">
  <div style="display:inline-block;">
    <div style="background:#111;color:#fff;padding:6px 16px;font-size:13px;font-weight:700;display:inline-block;margin-bottom:4px;">Payment Info:</div>
    <div style="font-size:13px;padding:3px 2px;">Paybill: <strong>{{ $paybill ?: '—' }}</strong></div>
    <div style="font-size:13px;padding:3px 2px;">Account: <strong>{{ $paybillAcct ?: '—' }}</strong></div>
  </div>
</div>

<!-- ═══ FOOTER: curved swoosh (mirrored, anchored right) with social icons ═══ -->
<div style="position:absolute;left:0;right:0;bottom:0;height:64px;overflow:visible;">
  <svg viewBox="0 0 794 64" width="100%" height="64" preserveAspectRatio="none" style="position:absolute;inset:0;overflow:visible;">
    <path d="M794,64 L154,64 C 314,61 294,37 414,24 C 494,16 594,6 644,0 L794,0 Z"
          fill="#00BCD4" style="filter:drop-shadow(-11px -9px 3px #F44336);"/>
  </svg>
  <div style="position:absolute;right:20px;top:50%;transform:translateY(-50%);z-index:2;display:flex;align-items:center;gap:8px;">
    <div style="width:30px;height:30px;border-radius:50%;background:#F44336;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="14" height="14" fill="white" viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
    </div>
    <div style="width:30px;height:30px;border-radius:50%;background:#1877F2;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="13" height="13" fill="white" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
    </div>
    <div style="width:30px;height:30px;border-radius:50%;background:radial-gradient(circle at 30% 107%,#fdf497 0%,#fdf497 5%,#fd5949 45%,#d6249f 60%,#285AEB 90%);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="13" height="13" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
    </div>
    <div style="width:30px;height:30px;border-radius:50%;background:#25D366;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="14" height="14" fill="white" viewBox="0 0 24 24"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 004.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2z"/></svg>
    </div>
    <div style="color:#fff;margin-left:6px;">
      <div style="font-size:13px;font-weight:800;line-height:1.3;">{{ strtolower($companyName) }}</div>
      <div style="font-size:13px;font-weight:800;line-height:1.3;">{{ $contactLine }}</div>
    </div>
  </div>
</div>

</div>
</body></html>
