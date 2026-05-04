<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(string $provider)
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            Log::warning('Auth[social]: błąd logowania ' . $provider, ['error' => $e->getMessage(), 'ip' => request()->ip()]);
            return redirect()->route('tenant.client.login')
                ->with('error', 'Nie udało się zalogować przez ' . ucfirst($provider) . '.');
        }

        $providerIdField = $provider . '_id';

        // Find existing customer by provider ID or email
        $customer = Customer::where($providerIdField, $socialUser->getId())
            ->orWhere('email', $socialUser->getEmail())
            ->first();

        $isNew = false;
        if ($customer) {
            // Update provider ID if not set
            if (!$customer->$providerIdField) {
                $customer->update([$providerIdField => $socialUser->getId()]);
            }
            if ($socialUser->getAvatar() && !$customer->avatar) {
                $customer->update(['avatar' => $socialUser->getAvatar()]);
            }
        } else {
            // Create new customer
            $customer = Customer::create([
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'Użytkownik',
                'email' => $socialUser->getEmail(),
                $providerIdField => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
            ]);
            $isNew = true;
        }

        Auth::guard('customer')->login($customer, true);
        request()->session()->regenerate();

        Log::info('Auth[social]: zalogowano przez ' . $provider, [
            'customer_id' => $customer->id,
            'email'       => $customer->email,
            'new_account' => $isNew,
            'ip'          => request()->ip(),
        ]);

        return redirect()->route('tenant.menu')
            ->with('success', 'Zalogowano przez ' . ucfirst($provider) . '!');
    }

    protected function validateProvider(string $provider): void
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            abort(404);
        }
    }
}
