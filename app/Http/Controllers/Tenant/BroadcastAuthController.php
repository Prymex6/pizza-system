<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

class BroadcastAuthController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        \Log::info('[BroadcastAuth] attempt', [
            'channel'   => $request->channel_name,
            'socket_id' => $request->socket_id,
            'user_id'   => $user?->id,
            'tenant'    => tenancy()->tenant?->id,
        ]);

        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        try {
            $result = Broadcast::auth($request);
            \Log::info('[BroadcastAuth] OK', ['channel' => $request->channel_name]);
            return $result;
        } catch (\Throwable $e) {
            \Log::warning('[BroadcastAuth] FAILED', [
                'channel' => $request->channel_name,
                'error'   => $e->getMessage(),
                'user_id' => $user?->id,
            ]);
            throw $e;
        }
    }
}
