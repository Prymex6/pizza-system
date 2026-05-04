<?php

namespace App\Http\Middleware;

use App\Models\Tenant\RolePermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = auth('tenant')->user();

        if (!$user) {
            return redirect()->route('tenant.login');
        }

        // Manager always has all permissions
        if ($user->role === 'manager') {
            return $next($request);
        }

        if (!RolePermission::roleHas($user->role, $permission)) {
            if ($request->wantsJson() || $request->inertia()) {
                return response()->json(['message' => 'Brak uprawnień.'], 403);
            }

            return redirect()->route('tenant.staff.redirect')
                ->with('error', 'Nie masz uprawnień do tej akcji.');
        }

        return $next($request);
    }
}
