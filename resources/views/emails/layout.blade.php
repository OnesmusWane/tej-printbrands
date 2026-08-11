@php
    $settings = \App\Models\SiteSetting::pluck('value', 'key');
    $company = $settings->get('company', []);
    $contact = $settings->get('contact', []);
    $socials = $settings->get('socials', []);

    $companyName = $company['name'] ?? $company['company_name'] ?? 'Tej Printbrands';
    $logoUrl = $company['logo_url'] ?? null;
    $address = $contact['address'] ?? '';
    $phone = $contact['phone'] ?? '';
    $contactEmail = $contact['email'] ?? '';

    $socialLinks = collect($socials)
        ->filter(fn ($url) => filled($url) && $url !== '#')
        ->map(fn ($url, $label) => ['label' => ucfirst($label), 'url' => $url]);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
<title>@yield('subject', $companyName)</title>
</head>
<body style="margin:0;padding:0;background:#F3F4F6;-webkit-text-size-adjust:100%;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F3F4F6;">
    <tr>
      <td align="center" style="padding:32px 16px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;font-family:Arial,Helvetica,sans-serif;">

          <!-- ═══ HEADER ═══ -->
          <tr>
            <td align="center" style="background:#00BCD4;padding:32px 32px 26px;">
              @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $companyName }}" height="44" style="display:block;margin:0 auto 10px;max-height:44px;">
              @else
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 10px;">
                  <tr><td width="48" height="48" align="center" valign="middle" style="background:#ffffff;border-radius:12px;font-family:Arial,Helvetica,sans-serif;font-size:18px;font-weight:bold;color:#00BCD4;">{{ strtoupper(substr($companyName, 0, 2)) }}</td></tr>
                </table>
              @endif
              <div style="color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-weight:bold;font-size:15px;letter-spacing:0.5px;">{{ strtoupper($companyName) }}</div>
            </td>
          </tr>

          <!-- ═══ BODY ═══ -->
          <tr>
            <td style="padding:36px 32px;font-family:Arial,Helvetica,sans-serif;color:#1F2937;">
              @yield('content')
            </td>
          </tr>

          <!-- ═══ FOOTER ═══ -->
          <tr>
            <td style="background:#F9FAFB;border-top:1px solid #E5E7EB;padding:24px 32px;font-family:Arial,Helvetica,sans-serif;">
              <p style="margin:0 0 4px;font-size:13px;font-weight:bold;color:#374151;">{{ $companyName }}</p>
              @if ($address)
                <p style="margin:0 0 4px;font-size:12px;color:#6B7280;">{{ $address }}</p>
              @endif
              @if ($phone || $contactEmail)
                <p style="margin:0 0 12px;font-size:12px;color:#6B7280;">{{ collect([$phone, $contactEmail])->filter()->implode(' · ') }}</p>
              @endif

              @if ($socialLinks->isNotEmpty())
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 12px;">
                  <tr>
                    @foreach ($socialLinks as $social)
                      <td style="padding-right:12px;">
                        <a href="{{ $social['url'] }}" style="font-size:12px;color:#00BCD4;text-decoration:none;">{{ $social['label'] }}</a>
                      </td>
                    @endforeach
                  </tr>
                </table>
              @endif

              <p style="margin:0;font-size:11px;color:#9CA3AF;">&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
