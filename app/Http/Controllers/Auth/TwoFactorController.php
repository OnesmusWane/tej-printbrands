<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Mail\TwoFactorCode as TwoFactorMail;
use App\Models\TwoFactorCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
class TwoFactorController extends Controller {
    public function show(): View|RedirectResponse {
        if (!session()->has('2fa_user_id')) return redirect()->route('login');
        return view('auth.two-factor');
    }
    public function verify(Request $request): RedirectResponse {
        $request->validate(['code' => ['required', 'string', 'size:6']]);
        $userId = session('2fa_user_id');
        if (!$userId) return redirect()->route('login')->withErrors(['code' => 'Session expired. Please login again.']);
        $record = TwoFactorCode::where('user_id', $userId)->where('code', $request->code)->whereNull('used_at')->where('expires_at', '>', now())->latest()->first();
        if (!$record) return back()->withErrors(['code' => 'Invalid or expired code.'])->withInput();
        $record->update(['used_at' => now()]);
        Auth::loginUsingId($userId, session('2fa_remember', false));
        session()->forget(['2fa_user_id', '2fa_remember']);
        return redirect()->intended(route('admin.dashboard'));
    }
    public function resend(): RedirectResponse {
        $userId = session('2fa_user_id');
        if (!$userId) return redirect()->route('login');
        $user = User::findOrFail($userId);
        self::sendCode($user);
        return back()->with('resent', true);
    }
    public static function sendCode(User $user): void {
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        TwoFactorCode::where('user_id', $user->id)->whereNull('used_at')->update(['used_at' => now()]);
        TwoFactorCode::create(['user_id' => $user->id, 'code' => $code, 'expires_at' => now()->addMinutes(10)]);
        Mail::to($user->email)->send(new TwoFactorMail($code, $user->name));
    }
}
