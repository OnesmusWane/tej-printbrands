<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\TwoFactorController;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function login(): View
    {
        return view('auth.login');
    }

    public function register(): View
    {
        return view('pages.account.register');
    }

    public function storeRegistration(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('account.dashboard'));
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'These account details do not match our records.']);
        }

        if ($user->is_admin) {
            $request->session()->put('2fa_user_id', $user->id);
            $request->session()->put('2fa_remember', $request->boolean('remember'));
            TwoFactorController::sendCode($user);
            return redirect()->route('admin.2fa');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('account.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $isStaff = Auth::check() && Auth::user()->is_admin;

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $isStaff ? redirect()->route('login') : redirect()->route('home');
    }

    public function dashboard(): View
    {
        return view('pages.account.dashboard', [
            'orders' => Auth::user()->orders()->latest()->limit(5)->get(),
        ]);
    }

    public function profile(): View
    {
        return view('pages.account.profile');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update($validated);

        return back()->with('account_success', 'Your profile has been updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Auth::user()->update(['password' => Hash::make($validated['password'])]);

        return back()->with('account_success', 'Your password has been changed.');
    }

    public function requestDeletion(Request $request): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['required', 'in:DELETE'],
        ]);

        Auth::user()->update(['deletion_requested_at' => now()]);

        return back()->with('account_success', 'Your account deletion request has been recorded. Our team will contact you before final removal.');
    }

    public function orders(): View
    {
        return view('pages.account.orders', [
            'orders' => Auth::user()->orders()->latest()->paginate(10),
        ]);
    }

    public function showOrder(Order $order): View
    {
        $this->authorizeOrder($order);

        return view('pages.account.order-show', compact('order'));
    }

    public function mpesa(Order $order): View
    {
        $this->authorizeOrder($order);

        abort_if($order->payment_method !== 'mpesa', 404);

        return view('pages.account.mpesa', compact('order'));
    }

    public function confirmMpesa(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        $validated = $request->validate([
            'mpesa_code' => ['required', 'string', 'min:8', 'max:30'],
        ]);

        $order->update([
            'mpesa_code' => strtoupper($validated['mpesa_code']),
            'payment_status' => 'paid',
            'status' => 'processing',
        ]);

        return redirect()->route('account.orders.payment.success', $order);
    }

    public function failMpesa(Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        $order->update([
            'payment_status' => 'failed',
            'status' => 'payment_failed',
        ]);

        return redirect()->route('account.orders.payment.failed', $order);
    }

    public function paymentSuccess(Order $order): View
    {
        $this->authorizeOrder($order);

        return view('pages.account.payment-success', compact('order'));
    }

    public function paymentFailed(Order $order): View
    {
        $this->authorizeOrder($order);

        return view('pages.account.payment-failed', compact('order'));
    }

    private function authorizeOrder(Order $order): void
    {
        abort_unless($order->user_id === Auth::id(), 403);
    }
}
