<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OrderTrackingController extends Controller
{
    public static function generateToken(string $orderNumber): string
    {
        return hash_hmac('sha256', $orderNumber, config('app.key'));
    }

    /**
     * Display order tracking page
     */
    public function show(Request $request, string $orderNumber)
    {
        $token = $request->query('token');
        $query = Order::with(['items.product', 'items.variant', 'deliveryZone', 'table'])
            ->where('order_number', $orderNumber);

        if ($token) {
            // Token provided — verify against DB-stored tracking_token
            $order = $query->firstOrFail();
            $tokenValid = $order->tracking_token && hash_equals($order->tracking_token, (string) $token);
            // Also accept legacy HMAC tokens for orders created before this change
            $legacyTokenValid = hash_equals(self::generateToken($orderNumber), (string) $token);
            $isStaff = Auth::guard('tenant')->check();
            $customer = Auth::guard('customer')->user();
            $isOwner = $customer && $order->customer_id && $customer->id === $order->customer_id;
            if (!$tokenValid && !$legacyTokenValid && !$isOwner && !$isStaff) {
                abort(403);
            }
        } else {
            // No token — only the owning customer or staff may view
            $customer = Auth::guard('customer')->user();
            $isStaff = Auth::guard('tenant')->check();
            if (!$customer && !$isStaff) {
                abort(404);
            }
            if ($customer && !$isStaff) {
                $query->where('customer_id', $customer->id);
            }
            $order = $query->firstOrFail();
        }

        $estimatedDeliveryTime = $this->getEstimatedDeliveryTime($order);

        return Inertia::render('Tenant/Client/OrderTracking', [
            'order' => $order,
            'estimatedDeliveryTime' => $estimatedDeliveryTime,
            'driverTrackingEnabled' => (bool) \App\Models\Tenant\Setting::get('driver_tracking_enabled', false),
        ]);
    }

    /**
     * Return estimated delivery time — use stored value if set, otherwise fall back to live calculation.
     * The stored value is set once at order creation and does not change on every page load.
     */
    private function getEstimatedDeliveryTime(Order $order): ?string
    {
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return null;
        }

        $timezone = \App\Models\Tenant\Setting::get('timezone', 'Europe/Warsaw') ?: 'Europe/Warsaw';

        // Use the stored timestamp set at order creation
        if ($order->estimated_delivery_time) {
            return \Carbon\Carbon::createFromTimestamp($order->estimated_delivery_time->timestamp, $timezone)->format('H:i');
        }

        // Fallback: calculate live (for orders created before this field was populated)
        $preparationTime = match ($order->status) {
            'pending', 'paid' => 30,
            'preparing'       => 20,
            'ready'           => 10,
            'on_delivery'     => 5,
            default           => 30,
        };

        if ($order->type === 'delivery' && $order->deliveryZone) {
            $preparationTime += $order->deliveryZone->estimated_time ?? 15;
        }

        return now()->setTimezone($timezone)->addMinutes($preparationTime)->format('H:i');
    }
}
