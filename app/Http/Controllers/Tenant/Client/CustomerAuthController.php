<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Helpers\PhoneHelper;
use App\Mail\Tenant\CustomerWelcomeMail;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Setting;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CustomerAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('tenant.account');
        }

        return Inertia::render('Tenant/Client/Auth/Login');
    }

    public function showRegister()
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('tenant.account');
        }

        return Inertia::render('Tenant/Client/Auth/Register');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            Log::info('Auth[customer]: zalogowano', ['email' => $credentials['email'], 'ip' => $request->ip()]);

            return redirect()->intended(route('tenant.menu'));
        }

        Log::warning('Auth[customer]: nieudane logowanie', ['email' => $credentials['email'], 'ip' => $request->ip()]);

        throw ValidationException::withMessages([
            'email' => 'Podane dane logowania są nieprawidłowe.',
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:customers,email',
            'password'       => 'required|string|min:8|confirmed',
            'phone'          => 'nullable|string|max:30',
            'referral_code'  => 'nullable|string|max:10',
            'terms_accepted' => 'required|accepted',
        ]);

        // Resolve referrer
        $referrer = null;
        if (!empty($validated['referral_code'])) {
            $referrer = Customer::where('referral_code', strtoupper($validated['referral_code']))->first();
        }

        $customer = Customer::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => $validated['password'],
            'phone'             => PhoneHelper::normalize($validated['phone'] ?? null),
            'loyalty_referred_by' => $referrer?->id,
        ]);

        Auth::guard('customer')->login($customer, true);
        $request->session()->regenerate();

        Log::info('Auth[customer]: nowe konto', [
            'customer_id' => $customer->id,
            'email'       => $customer->email,
            'referrer_id' => $referrer?->id,
            'ip'          => $request->ip(),
        ]);

        app(LoyaltyService::class)->awardRegistrationBonus($customer);

        Mail::to($customer->email)
            ->queue(new CustomerWelcomeMail(
                customerName: $customer->name,
                restaurantName: Setting::get('restaurant_name', 'Restauracja'),
            ));

        return redirect()->route('tenant.menu')
            ->with('success', 'Konto zostało utworzone!');
    }

    public function logout(Request $request)
    {
        $email = Auth::guard('customer')->user()?->email;
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Log::info('Auth[customer]: wylogowano', ['email' => $email, 'ip' => $request->ip()]);

        return redirect()->route('tenant.menu');
    }

    public function showForgotPassword()
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('tenant.account');
        }
        return Inertia::render('Tenant/Client/Auth/ForgotPassword');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::broker('customers')->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            Log::info('Auth[customer]: link reset hasła wysłany', ['email' => $request->email]);
            return back()->with('success', 'Link do resetowania hasła został wysłany na podany adres e-mail.');
        }

        return back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(Request $request, string $token)
    {
        $email = $request->email;
        $tokenValid = false;
        if ($email) {
            $customer = \App\Models\Tenant\Customer::where('email', $email)->first();
            if ($customer) {
                $tokenValid = Password::broker('customers')->tokenExists($customer, $token);
            }
        }

        return Inertia::render('Tenant/Client/Auth/ResetPassword', [
            'token'      => $token,
            'email'      => $email,
            'tokenValid' => $tokenValid,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            Log::info('Auth[customer]: hasło zresetowane', ['email' => $request->email]);
            return redirect()->route('tenant.client.login')
                ->with('status', 'Hasło zostało zmienione. Możesz się teraz zalogować.');
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
