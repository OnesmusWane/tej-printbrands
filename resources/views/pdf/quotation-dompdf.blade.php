@php
    $company = $settings['company'] ?? [];
    $contact = $settings['contact'] ?? [];
    $business = $settings['business'] ?? [];

    $companyName = $company['name'] ?? $company['company_name'] ?? 'Tej Printbrands';
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
<style>
@page { margin: 0; }
* { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; color: #1F2937; }
body { margin: 0; padding: 0; }
table { border-collapse: collapse; width: 100%; }
</style>
</head><body>

<div style="border:4px solid #111;width:794px;">

  <!-- ═══ HEADER: pre-rendered curve background + dynamic logo/company/date table ═══ -->
  <div style="position:relative;width:794px;height:210px;">
    <img src="{{ $headerBg }}" style="position:absolute;top:0;left:0;width:794px;height:210px;">

    <div style="position:absolute;left:26px;top:24px;">
      <table style="width:auto;">
        <tr>
          <td style="width:82px;">
            <div style="width:82px;height:82px;border-radius:50%;background:#fff;text-align:center;line-height:82px;">
              @if ($logoUrl)
                <img src="{{ $logoUrl }}" style="width:74px;vertical-align:middle;">
              @else
                <span style="font-size:28px;font-weight:700;color:#1a237e;">T</span>
              @endif
            </div>
          </td>
          <td style="padding-left:16px;color:#fff;vertical-align:middle;">
            <div style="font-size:16px;font-weight:700;letter-spacing:1px;">{{ strtoupper($companyName) }}</div>
            <div style="font-size:11px;margin-top:7px;line-height:1.6;">{{ $addr1 }}@if($addr2)<br>{{ $addr2 }}@endif</div>
          </td>
        </tr>
      </table>
    </div>

    <div style="position:absolute;right:30px;bottom:14px;width:300px;">
      <div style="font-size:32px;font-weight:700;letter-spacing:2px;text-align:right;margin-bottom:10px;">QUOTATION</div>
      <table style="font-size:11px;">
        <tr><td style="border:1px solid #999;padding:5px 10px;width:90px;">Date:</td><td style="border:1px solid #999;padding:5px 10px;">{{ $q->created_at->format('d M Y') }}</td></tr>
        <tr><td style="border:1px solid #999;padding:5px 10px;">Quote:</td><td style="border:1px solid #999;padding:5px 10px;">{{ $q->quote_number }}</td></tr>
        <tr><td style="border:1px solid #999;padding:5px 10px;">Customer ID:</td><td style="border:1px solid #999;padding:5px 10px;">{{ $q->client }}</td></tr>
      </table>
    </div>
  </div>

  <!-- ═══ ITEMS TABLE ═══ -->
  <div style="padding:26px 26px 8px;">
    <table>
      <thead>
        <tr style="background:#111;">
          <th style="padding:10px 14px;text-align:left;font-size:12px;letter-spacing:.5px;color:#fff;">ITEM</th>
          <th style="padding:10px 14px;text-align:center;font-size:12px;letter-spacing:.5px;color:#fff;">QTY</th>
          <th style="padding:10px 8px;text-align:right;font-size:11px;color:#fff;">UNIT PRICE</th>
          <th style="padding:10px 14px;text-align:right;font-size:12px;letter-spacing:.5px;color:#fff;">TOTAL</th>
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
          <tr><td colspan="4" style="height:26px;border-bottom:1px solid #00BCD4;">&nbsp;</td></tr>
        @endfor
      </tbody>
    </table>

    <!-- Right-aligned breakdown + TOTAL bar, via a spacer column instead of flexbox -->
    <table style="margin-top:8px;">
      <tr>
        <td style="width:52%;">&nbsp;</td>
        <td style="width:48%;">
          @if ($q->vat_included !== false)
            <table>
              <tr><td style="padding:4px 16px;font-size:12px;color:#6B7280;">Subtotal</td><td style="padding:4px 16px;font-size:12px;text-align:right;">Ksh {{ number_format($q->subtotal) }}</td></tr>
              <tr><td style="padding:4px 16px;font-size:12px;color:#6B7280;border-bottom:1px solid #e5e7eb;">VAT (16%)</td><td style="padding:4px 16px;font-size:12px;text-align:right;border-bottom:1px solid #e5e7eb;">Ksh {{ number_format($q->tax) }}</td></tr>
            </table>
          @endif
          <table style="background:#111;margin-top:6px;">
            <tr>
              <td style="padding:10px 16px;color:#fff;font-weight:700;font-size:14px;">TOTAL{{ $q->vat_included === false ? ' (VAT Exempt)' : '' }}</td>
              <td style="padding:10px 16px;color:#fff;font-weight:700;font-size:14px;text-align:right;">Ksh {{ number_format($q->total) }}</td>
            </tr>
          </table>
        </td>
      </tr>
    </table>

    @if ($q->terms)
      <div style="margin-top:14px;font-size:11px;color:#6B7280;padding-top:10px;border-top:1px solid #e5e7eb;"><strong>Terms &amp; Conditions:</strong> {{ $q->terms }}</div>
    @endif
  </div>

  <!-- ═══ PAYMENT INFO ═══ -->
  <div style="padding:20px 26px 0;">
    <table style="width:auto;">
      <tr><td style="background:#111;color:#fff;padding:6px 16px;font-size:13px;font-weight:700;">Payment Info:</td></tr>
    </table>
    <div style="font-size:13px;padding:6px 2px 0;">Paybill: <strong>{{ $paybill ?: '—' }}</strong></div>
    <div style="font-size:13px;padding:3px 2px;">Account: <strong>{{ $paybillAcct ?: '—' }}</strong></div>
  </div>

  <!-- ═══ FOOTER: pre-rendered curve + icons background, dynamic contact text overlaid ═══ -->
  <div style="position:relative;width:794px;height:64px;margin-top:20px;">
    <img src="{{ $footerBg }}" style="position:absolute;bottom:0;left:0;width:794px;height:64px;">
    <div style="position:absolute;right:20px;bottom:16px;text-align:right;color:#fff;">
      <div style="font-size:13px;font-weight:700;">{{ strtolower($companyName) }}</div>
      <div style="font-size:13px;font-weight:700;">{{ $contactLine }}</div>
    </div>
  </div>

</div>
</body></html>
