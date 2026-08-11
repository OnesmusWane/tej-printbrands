@extends('emails.layout')

@section('subject', 'Your Admin Login Code')

@section('content')
  <h2 style="margin:0 0 8px;font-size:22px;font-weight:bold;color:#1F2937;">Your Admin Login Code</h2>
  <p style="margin:0 0 28px;font-size:15px;line-height:1.6;color:#6B7280;">
    Hello {{ $name }}, enter this code to complete your sign-in.
  </p>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;margin-bottom:24px;">
    <tr>
      <td align="center" style="padding:28px;">
        <span style="font-family:Arial,Helvetica,sans-serif;font-size:38px;font-weight:bold;letter-spacing:0.25em;color:#1F2937;">{{ $code }}</span>
      </td>
    </tr>
  </table>

  <p style="margin:0;font-size:13px;line-height:1.6;color:#9CA3AF;">
    This code expires in 10 minutes. If you did not request this, you can safely ignore this email.
  </p>
@endsection
