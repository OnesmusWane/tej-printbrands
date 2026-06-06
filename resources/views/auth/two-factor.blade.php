<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Two-Factor Authentication | Tej Printbrands</title>
@vite(['resources/css/app.css'])
</head>
<body class="h-full font-sans">
<div class="min-h-screen flex" style="background:#F9FAFB;">

  {{-- Left visual panel --}}
  <div class="hidden lg:flex w-1/2 relative overflow-hidden text-white p-12 flex-col justify-between" style="background:linear-gradient(to bottom right,#0097a7,#006064,#0f172a);">
    <div class="absolute rounded-full blur-3xl" style="top:-10%;left:-10%;width:60%;height:60%;background:rgba(0,188,212,0.2);"></div>
    <div class="absolute rounded-full blur-3xl" style="bottom:-10%;right:-10%;width:60%;height:60%;background:rgba(0,150,136,0.1);"></div>
    <div class="absolute inset-0" style="opacity:0.04;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40'%3E%3Cpath d='M0 10h40M10 0v40M0 20h40M20 0v40M0 30h40M30 0v40' stroke='%23fff' stroke-width='1' fill='none'/%3E%3C/svg%3E\");"></div>
    <div class="relative z-10">
      <a href="/" class="flex items-center gap-3 group">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center font-bold text-xl transition-transform group-hover:scale-110" style="background:#00BCD4;">TJ</div>
        <span class="text-2xl font-bold tracking-tight">Tej <span style="color:#4dd0e1;">Printbrands</span></span>
      </a>
    </div>
    <div class="relative z-10 max-w-md">
      <h1 class="text-4xl xl:text-5xl font-bold leading-tight mb-6">Manage your creative empire with confidence.</h1>
      <p class="text-lg leading-relaxed mb-8" style="color:rgba(236,254,255,0.8);">Access analytics, manage projects, handle clients, and oversee operations — all from one premium dashboard.</p>
      <div class="flex items-center gap-6">
        <div><p class="text-3xl font-bold">500+</p><p class="text-sm" style="color:#4dd0e1;">Active Projects</p></div>
        <div class="w-px h-12" style="background:rgba(255,255,255,0.2);"></div>
        <div><p class="text-3xl font-bold">99%</p><p class="text-sm" style="color:#4dd0e1;">Uptime</p></div>
        <div class="w-px h-12" style="background:rgba(255,255,255,0.2);"></div>
        <div><p class="text-3xl font-bold">24/7</p><p class="text-sm" style="color:#4dd0e1;">Support</p></div>
      </div>
    </div>
    <div class="relative z-10 text-sm" style="color:rgba(165,243,252,0.6);">&copy; {{ date('Y') }} Tej Printbrands. All rights reserved.</div>
  </div>

  {{-- Right form panel --}}
  <div class="flex-1 flex flex-col">
    <div class="p-6 lg:hidden">
      <a href="/" class="flex items-center gap-2">
        <div class="w-9 h-9 rounded-lg flex items-center justify-center font-bold text-white" style="background:#00BCD4;">TJ</div>
        <span class="font-bold" style="color:#1F2937;">Tej Printbrands</span>
      </a>
    </div>
    <div class="flex-1 flex items-center justify-center p-6 sm:p-12">
      <div class="w-full max-w-md">
        <a href="{{ route('login') }}" class="flex items-center gap-2 text-sm mb-6 transition-colors" style="color:#6B7280;" onmouseover="this.style.color='#1F2937'" onmouseout="this.style.color='#6B7280'">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          Back to login
        </a>
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-6" style="background:rgba(0,188,212,0.1);">
          <svg class="w-8 h-8" fill="none" stroke="#00BCD4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        </div>
        <div class="mb-8">
          <h2 class="text-3xl font-bold mb-2" style="color:#1F2937;">Two-Factor Authentication</h2>
          <p style="color:#6B7280;">We've sent a 6-digit verification code to your email. Enter it below to continue.</p>
        </div>
        @if ($errors->any())
          <div class="mb-5 rounded-xl border p-4 text-sm" style="background:#FEF2F2;border-color:#FECACA;color:#B91C1C;">{{ $errors->first() }}</div>
        @endif
        @if (session('resent'))
          <div class="mb-5 rounded-xl border p-4 text-sm" style="background:#F0FDF4;border-color:#BBF7D0;color:#15803D;">A new code has been sent to your email.</div>
        @endif
        <form method="POST" action="{{ route('admin.2fa.verify') }}" class="space-y-6" id="tfa-form">
          @csrf
          <div>
            <label class="block text-sm font-medium mb-3" style="color:#374151;">Verification Code</label>
            <div class="flex justify-between gap-2 sm:gap-3">
              @for($i = 0; $i < 6; $i++)
                <input type="text" inputmode="numeric" maxlength="1" data-idx="{{ $i }}"
                  class="code-digit w-full aspect-square text-center text-2xl font-bold border-2 border-gray-200 rounded-xl outline-none transition-all"
                  style="color:#1F2937;">
              @endfor
              <input type="hidden" name="code" id="code-value">
            </div>
          </div>
          <button type="submit" id="verify-btn" disabled class="w-full py-3.5 rounded-xl font-semibold transition-all cursor-not-allowed" style="background:#F3F4F6;color:#9CA3AF;">
            Verify &amp; Continue
          </button>
        </form>
        <div class="mt-6 text-center text-sm" id="resend-wrap" style="color:#6B7280;">
          Didn't receive a code? Resend in <strong id="resend-timer" style="color:#1F2937;">45s</strong>
        </div>
        <form method="POST" action="{{ route('admin.2fa.resend') }}" class="text-center mt-2 hidden" id="resend-form">
          @csrf
          <button type="submit" class="text-sm font-semibold transition-colors" style="color:#00BCD4;" onmouseover="this.style.color='#006064'" onmouseout="this.style.color='#00BCD4'">
            Resend code
          </button>
        </form>
        <div class="mt-8 p-4 rounded-xl border text-sm" style="background:#F9FAFB;border-color:#E5E7EB;color:#4B5563;">
          <p class="font-medium mb-1" style="color:#1F2937;">💡 Tip</p>
          Check your email inbox (and spam folder if needed). The code expires in 10 minutes.
        </div>
      </div>
    </div>
  </div>

</div>
<script>
const inputs = document.querySelectorAll('.code-digit');
const hidden  = document.getElementById('code-value');
const btn     = document.getElementById('verify-btn');
const wrap    = document.getElementById('resend-wrap');
const form    = document.getElementById('resend-form');
const timerEl = document.getElementById('resend-timer');
let seconds = 45;
const tick = setInterval(() => {
  seconds--;
  if (seconds <= 0) {
    clearInterval(tick);
    wrap.classList.add('hidden');
    form.classList.remove('hidden');
  } else {
    timerEl.textContent = seconds + 's';
  }
}, 1000);
function update() {
  const c = Array.from(inputs).map(i => i.value).join('');
  hidden.value = c;
  if (c.length === 6) {
    btn.disabled = false;
    btn.style.background = '#1F2937'; btn.style.color = 'white'; btn.style.cursor = 'pointer';
    btn.style.boxShadow = '0 10px 15px -3px rgba(31,41,55,0.2)';
  } else {
    btn.disabled = true;
    btn.style.background = '#F3F4F6'; btn.style.color = '#9CA3AF'; btn.style.cursor = 'not-allowed';
    btn.style.boxShadow = 'none';
  }
}
inputs.forEach((inp, idx) => {
  inp.addEventListener('focus', e => { e.target.style.borderColor = '#00BCD4'; e.target.style.boxShadow = '0 0 0 4px rgba(0,188,212,0.15)'; });
  inp.addEventListener('blur',  e => { if (!e.target.value) { e.target.style.borderColor = '#E5E7EB'; e.target.style.boxShadow = 'none'; } });
  inp.addEventListener('input', e => {
    const v = e.target.value.replace(/\D/g, '');
    e.target.value = v.slice(-1);
    if (v && idx < 5) inputs[idx + 1].focus();
    update();
  });
  inp.addEventListener('keydown', e => {
    if (e.key === 'Backspace' && !e.target.value && idx > 0) inputs[idx - 1].focus();
  });
  inp.addEventListener('paste', e => {
    e.preventDefault();
    const p = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
    p.split('').forEach((ch, i) => { if (inputs[i]) inputs[i].value = ch; });
    if (inputs[Math.min(p.length, 5)]) inputs[Math.min(p.length, 5)].focus();
    update();
  });
});
</script>
</body>
</html>
