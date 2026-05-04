<?php

namespace App\Http\Controllers\Tenant\Staff;

use App\Events\DriverLocationUpdated;
use App\Events\OrderStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DriverController extends Controller
{
    /**
     * Display driver panel with delivery orders
     */
    public function index()
    {
        $driver = Auth::guard('tenant')->user();

        // Pobierz zamówienia do dostawy
        // - Status 'ready' - gotowe do odbioru z restauracji
        // - Status 'on_delivery' - przypisane do kierowcy (w drodze)
        $orders = Order::with(['items.product', 'items.variant', 'deliveryZone'])
            ->where('type', 'delivery')
            ->whereIn('status', ['ready', 'on_delivery'])
            ->where(function ($query) use ($driver) {
                $query->whereNull('driver_id')
                    ->orWhere('driver_id', $driver->id);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // Zamówienia kierowcy (w trakcie dostawy)
        $myDeliveries = $orders->where('driver_id', $driver->id);

        // Dostępne zamówienia do odbioru
        $availableDeliveries = $orders->where('driver_id', null);

        return Inertia::render('Tenant/Staff/Driver', [
            'myDeliveries' => $myDeliveries->values(),
            'availableDeliveries' => $availableDeliveries->values(),
            'driverTrackingEnabled' => (bool) \App\Models\Tenant\Setting::get('driver_tracking_enabled', false),
        ]);
    }

    /**
     * Assign delivery to driver
     */
    public function assignToMe(Request $request, Order $order)
    {
        $driver = Auth::guard('tenant')->user();

        if ($order->driver_id !== null) {
            return back()->with('error', 'To zamówienie jest już przypisane do innego kierowcy');
        }

        if ($order->status !== 'ready') {
            return back()->with('error', 'To zamówienie nie jest jeszcze gotowe do dostawy');
        }

        $oldStatus = $order->status;

        $order->update([
            'driver_id' => $driver->id,
            'status' => 'on_delivery',
        ]);

        Log::info('Driver: zamówienie przypisane', [
            'order_number' => $order->order_number,
            'driver_id'    => $driver->id,
            'driver_name'  => $driver->name,
        ]);

        event(new OrderStatusChanged($order, $oldStatus, 'on_delivery'));

        return back()->with('success', 'Zamówienie zostało przypisane do Ciebie');
    }

    /**
     * Mark delivery as completed
     */
    public function completeDelivery(Request $request, Order $order)
    {
        $driver = Auth::guard('tenant')->user();

        if ($order->driver_id !== $driver->id) {
            return back()->with('error', 'To zamówienie nie jest przypisane do Ciebie');
        }

        if ($order->status === 'cancelled') {
            return back()->with('error', 'Nie można zakończyć anulowanego zamówienia');
        }

        $oldStatus = $order->status;

        $updateData = ['status' => 'completed'];

        // Auto-mark payment as paid for cash/card-on-delivery orders
        if (in_array($order->payment_method, ['cash', 'card_on_delivery'])
            && $order->payment_status !== 'paid') {
            $updateData['payment_status'] = 'paid';
        }

        $order->update($updateData);

        Log::info('Driver: dostawa zakończona', [
            'order_number' => $order->order_number,
            'driver_id'    => $driver->id,
        ]);

        event(new OrderStatusChanged($order, $oldStatus, 'completed'));

        return back()->with('success', 'Dostawa została oznaczona jako zakończona');
    }

    /**
     * Broadcast driver GPS location to customer tracking page
     */
    public function updateLocation(Request $request, Order $order)
    {
        $driver = Auth::guard('tenant')->user();

        if ($order->driver_id !== $driver->id) {
            return response()->json(['error' => 'Brak dostępu'], 403);
        }

        $validated = $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        event(new DriverLocationUpdated($order, $validated['latitude'], $validated['longitude']));

        return response()->json(['ok' => true]);
    }
}
