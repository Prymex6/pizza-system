<?php

namespace App\Http\Controllers\Tenant\Staff;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'endpoint'    => ['required', 'string'],
            'p256dh_key'  => ['nullable', 'string'],
            'auth_key'    => ['nullable', 'string'],
            'expires_at'  => ['nullable', 'string'],
        ]);

        $staffUserId = Auth::guard('tenant')->id();

        PushSubscription::updateOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'staff_user_id' => $staffUserId,
                'role'          => Auth::guard('tenant')->user()?->role,
                'p256dh_key'    => $validated['p256dh_key'] ?? null,
                'auth_key'      => $validated['auth_key'] ?? null,
                'expires_at'    => isset($validated['expires_at']) ? date('Y-m-d H:i:s', $validated['expires_at'] / 1000) : null,
            ]
        );

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request)
    {
        $endpoint = $request->input('endpoint');
        if ($endpoint) {
            PushSubscription::where('endpoint', $endpoint)->delete();
        }
        return response()->json(['success' => true]);
    }
}
