<!DOCTYPE html><html><head><meta charset="utf-8"><title>Login Code</title></head>
<body style="font-family:sans-serif;max-width:480px;margin:40px auto;padding:20px;color:#1F2937;">
  <div style="text-align:center;margin-bottom:32px;"><div style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;background:#00BCD4;border-radius:12px;"><span style="color:white;font-weight:bold;font-size:20px;">TJ</span></div></div>
  <h2 style="font-size:24px;font-weight:700;margin-bottom:8px;">Your Admin Login Code</h2>
  <p style="color:#6B7280;margin-bottom:32px;">Hello {{ $name }}, enter this code to complete your sign-in.</p>
  <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;padding:24px;text-align:center;margin-bottom:24px;"><span style="font-size:40px;font-weight:800;letter-spacing:0.2em;color:#1F2937;">{{ $code }}</span></div>
  <p style="font-size:13px;color:#9CA3AF;">This code expires in 10 minutes. If you did not request this, ignore this email.</p>
</body></html>
