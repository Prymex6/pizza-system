<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ImpersonateController extends Controller
{
    /**
     * Handle incoming impersonation token from landlord.
     * Route: GET /manager/impersonate?token=...  (public, no auth required)
     */
    public function handle(Request $request)
    {
        $token = $request->query('token', '');

        if (!$token) {
            abort(403, 'Nieprawidłowy token impersonacji.');
        }

        // Use file store explicitly to bypass CacheTenancyBootstrapper (database store doesn't support tags)
        $data = Cache::store('file')->pull('impersonate:' . $token);

        if (!$data) {
            abort(403, 'Token impersonacji wygasł lub jest nieprawidłowy.');
        }

        $manager = User::find($data['user_id']);

        if (!$manager || $manager->role !== 'manager') {
            abort(403, 'Nie znaleziono konta managera.');
        }

        Auth::guard('tenant')->login($manager);

        // Store impersonation flag in session so we can show "Stop impersonating" banner
        session(['impersonating' => true]);

        return redirect()->route('tenant.manager.dashboard');
    }

    /**
     * Stop impersonation and redirect to landlord panel.
     * Route: POST /manager/impersonate/stop
     */
    public function stop(Request $request)
    {
        Auth::guard('tenant')->logout();
        $request->session()->forget('impersonating');

        $baseDomain = config('tenancy.central_domains')[0] ?? config('app.base_domain', 'localhost');
        $scheme     = request()->isSecure() || config('app.force_https') ? 'https' : 'http';

        // Redirect to landlord admin panel
        return redirect()->away($scheme . '://' . $baseDomain . '/admin/tenants');
    }
}
