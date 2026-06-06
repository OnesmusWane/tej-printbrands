<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Sign In | Tej Printbrands</title>
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
    <div class="p-6 flex justify-between items-center lg:hidden">
      <a href="/" class="flex items-center gap-2">
        <div class="w-9 h-9 rounded-lg flex items-center justify-center font-bold text-white" style="background:#00BCD4;">TJ</div>
        <span class="font-bold" style="color:#1F2937;">Tej Printbrands</span>
      </a>
    </div>
    <div class="flex-1 flex items-center justify-center p-6 sm:p-12">
      <div class="w-full max-w-md">
        <a href="/" class="hidden lg:flex items-center gap-2 text-sm mb-8 transition-colors" style="color:#6B7280;" onmouseover="this.style.color='#1F2937'" onmouseout="this.style.color='#6B7280'">
          ← Back to website
        </a>
        <div class="mb-8">
          <h2 class="text-3xl font-bold mb-2" style="color:#1F2937;">Welcome back 👋</h2>
          <p style="color:#6B7280;">Sign in to continue.</p>
        </div>
        @if ($errors->any())
          <div class="mb-5 rounded-xl border p-4 text-sm" style="background:#FEF2F2;border-color:#FECACA;color:#B91C1C;">{{ $errors->first() }}</div>
        @endif
        @if (session('status'))
          <div class="mb-5 rounded-xl border p-4 text-sm" style="background:#F0FDF4;border-color:#BBF7D0;color:#15803D;">{{ session('status') }}</div>
        @endif
        <form class="space-y-5" method="POST" action="{{ route('login.submit') }}">
          @csrf
          <div class="space-y-2">
            <label class="block text-sm font-medium" style="color:#374151;">Email Address</label>
            <div class="relative">
              <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="#9CA3AF" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@company.com"
                class="w-full pl-12 pr-4 py-3.5 border border-gray-300 rounded-xl outline-none transition-all text-sm"
                style="color:#1F2937;"
                onfocus="this.style.borderColor='#00BCD4';this.style.boxShadow='0 0 0 4px rgba(0,188,212,0.15)'"
                onblur="this.style.borderColor='#D1D5DB';this.style.boxShadow='none'">
            </div>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <label class="block text-sm font-medium" style="color:#374151;">Password</label>
              @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-xs font-semibold transition-colors" style="color:#00BCD4;" onmouseover="this.style.color='#006064'" onmouseout="this.style.color='#00BCD4'">Forgot password?</a>
              @endif
            </div>
            <div class="relative">
              <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="#9CA3AF" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
              <input type="password" name="password" id="pw" required placeholder="Enter your password"
                class="w-full pl-12 pr-12 py-3.5 border border-gray-300 rounded-xl outline-none transition-all text-sm"
                onfocus="this.style.borderColor='#00BCD4';this.style.boxShadow='0 0 0 4px rgba(0,188,212,0.15)'"
                onblur="this.style.borderColor='#D1D5DB';this.style.boxShadow='none'">
              <button type="button" onclick="var i=document.getElementById('pw');i.type=i.type==='password'?'text':'password';this.querySelector('span').textContent=i.type==='password'?'👁':'🙈'" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                <span>👁</span>
              </button>
            </div>
          </div>
          <label class="flex items-center gap-2 cursor-pointer pt-1">
            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300">
            <span class="text-sm" style="color:#4B5563;">Remember me for 30 days</span>
          </label>
          <button type="submit" class="w-full py-3.5 rounded-xl font-semibold text-white transition-all hover:-translate-y-0.5"
            style="background:#1F2937;box-shadow:0 10px 15px -3px rgba(31,41,55,0.2);"
            onmouseover="this.style.background='#374151'" onmouseout="this.style.background='#1F2937'">
            Sign In
          </button>
        </form>
        <div class="mt-8 pt-6 border-t border-gray-100">
          <button class="w-full flex items-center justify-center gap-2 text-sm transition-colors" style="color:#6B7280;" onmouseover="this.style.color='#00BCD4'" onmouseout="this.style.color='#6B7280'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Lost access to your authenticator?
          </button>
        </div>
        <p class="text-center mt-6 text-sm" style="color:#6B7280;">
          No account yet?
          <a href="{{ route('register') }}" class="font-semibold transition-colors" style="color:#00BCD4;" onmouseover="this.style.color='#006064'" onmouseout="this.style.color='#00BCD4'">Create one</a>
        </p>
      </div>
    </div>
  </div>

</div>
</body>
</html>
