<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Receipt {{ $receiptNo }}</title>
<style>
    @page { size: A5 landscape; margin: 12mm 14mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, Helvetica, sans-serif; color: #111; background: #fff; font-size: 13px; }
    table { border-collapse: collapse; width: 100%; }
    .dotted { border-bottom: 1.5px dotted #555; }
</style>
</head>
<body>

{{-- TOP HEADER --}}
<table style="border: 2px solid #1a237e; margin-bottom: 14px;">
    <tr>
        <td style="width: 90px; padding: 10px 14px; border-right: 2px solid #1a237e; text-align: center; vertical-align: middle;">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" style="max-width: 72px; max-height: 56px;" alt="">
            @else
                <span style="font-size: 16px; font-weight: 700; color: #1a237e; letter-spacing: 1px;">{{ strtoupper(explode(' ', $companyName)[0]) }}</span>
            @endif
        </td>
        <td style="padding: 10px 20px; border-right: 2px solid #1a237e; text-align: center; vertical-align: middle;">
            <div style="font-size: 28px; font-weight: 700; color: #1a237e; letter-spacing: 6px;">RECEIPT</div>
            <div style="font-size: 10px; color: #6B7280; margin-top: 2px;">No. {{ $receiptNo }}</div>
        </td>
        <td style="width: 170px; padding: 10px 14px; font-size: 11px; vertical-align: middle;">
            <div style="margin-bottom: 3px;"><strong>Tel:</strong> {{ $phone }}{{ $phoneSecondary ? ' / '.$phoneSecondary : '' }}</div>
            @if ($website)
                <div style="margin-bottom: 3px;"><strong>Web:</strong> {{ $website }}</div>
            @endif
            <div><strong>Email:</strong> {{ $email }}</div>
        </td>
    </tr>
</table>

{{-- DATE ROW --}}
<table style="margin-bottom: 10px;">
    <tr>
        <td style="text-align: right; font-size: 12px;"><strong>Date:</strong> {{ $paidDate }}</td>
    </tr>
</table>

{{-- RECEIVED FROM --}}
<table style="margin-bottom: 9px;">
    <tr>
        <td style="width: 110px; white-space: nowrap; font-weight: 700; vertical-align: bottom;">Received from</td>
        <td class="dotted" style="vertical-align: bottom; padding: 0 8px;">&nbsp;{{ $clientName }}&nbsp;</td>
        <td style="width: 140px; border: 1.5px solid #1a237e; padding: 5px 12px; text-align: center; font-weight: 700; font-size: 13px;">
            Kshs&nbsp;<span style="font-size: 15px;">{{ number_format($amount, 2) }}</span>
        </td>
    </tr>
</table>

{{-- AMOUNT IN WORDS --}}
<table style="margin-bottom: 9px;">
    <tr>
        <td style="width: 140px; white-space: nowrap; font-weight: 700; vertical-align: bottom;">Kenyan Shillings</td>
        <td class="dotted" style="vertical-align: bottom; padding: 0 8px;"></td>
        <td style="width: 260px; white-space: nowrap; font-size: 11px; font-style: italic; vertical-align: bottom; padding-left: 6px;">{{ $amountWords }}</td>
    </tr>
</table>

{{-- DESCRIPTION --}}
<table style="margin-bottom: 9px;">
    <tr>
        <td style="width: 190px; white-space: nowrap; color: #555; vertical-align: bottom;">(description of goods/services)</td>
        <td class="dotted" style="vertical-align: bottom;"></td>
    </tr>
</table>

{{-- BEING PAYMENT OF --}}
<table style="margin-bottom: 16px;">
    <tr>
        <td style="width: 130px; white-space: nowrap; font-weight: 700; vertical-align: bottom;">Being payment of</td>
        <td class="dotted" style="vertical-align: bottom; padding: 0 8px;">&nbsp;{{ $invoiceRef }}&nbsp;</td>
        <td style="width: 150px; font-size: 11px; color: #555; text-align: right; vertical-align: bottom;">{{ $reference ? 'Ref: '.$reference : '' }}</td>
    </tr>
</table>

{{-- PAYMENT METHOD + SIGNATURE --}}
<table style="border-top: 1.5px solid #1a237e; padding-top: 10px;">
    <tr>
        <td style="font-size: 13px; vertical-align: bottom; padding-top: 10px;">
            <span style="display: inline-block; width: 14px; height: 14px; border: 1.5px solid #1a237e; {{ $isCash ? 'background:#1a237e;color:#fff;' : '' }} margin-right: 3px; text-align: center; line-height: 12px; font-size: 11px;">{{ $isCash ? 'X' : '' }}</span>
            <span style="margin-right: 16px;">Cash</span>
            <span style="display: inline-block; width: 14px; height: 14px; border: 1.5px solid #1a237e; {{ $isMpesa ? 'background:#1a237e;color:#fff;' : '' }} margin-right: 3px; text-align: center; line-height: 12px; font-size: 11px;">{{ $isMpesa ? 'X' : '' }}</span>
            <span style="margin-right: 16px;">M-Pesa</span>
            <span style="display: inline-block; width: 14px; height: 14px; border: 1.5px solid #1a237e; {{ $isCheque ? 'background:#1a237e;color:#fff;' : '' }} margin-right: 3px; text-align: center; line-height: 12px; font-size: 11px;">{{ $isCheque ? 'X' : '' }}</span>
            <span>Cheque No.</span>
            <span class="dotted" style="display: inline-block; width: 90px; margin-left: 4px;">&nbsp;{{ $isCheque ? $reference : '' }}&nbsp;</span>
        </td>
        <td style="width: 160px; text-align: right; vertical-align: bottom; padding-top: 10px;">
            <div style="font-size: 11px; color: #555; margin-bottom: 4px;">Authorised Signature</div>
            <div style="border-bottom: 1.5px solid #555; height: 14px;"></div>
            <div style="font-size: 12px; font-weight: 700; margin-top: 4px; color: #1a237e;">FOR: {{ strtoupper(explode(' ', $companyName)[0]) }}</div>
        </td>
    </tr>
</table>

{{-- TAGLINE --}}
<table style="margin-top: 12px; border-top: 2px solid #1a237e;">
    <tr>
        <td style="padding-top: 8px; text-align: center; font-size: 9px; color: #6B7280; text-transform: uppercase; letter-spacing: 2px;">
            Graphic Design &bull; T-Shirts &bull; General Branding &bull; Digital Printing &bull; Signage &bull; Promotional Items
        </td>
    </tr>
</table>

</body>
</html>
