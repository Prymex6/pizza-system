<?php

namespace App\Http\Controllers\Tenant\Staff;

use App\Http\Controllers\Controller;
use App\Events\OrderStatusChanged;
use App\Models\Tenant\Order;
use App\Models\Tenant\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class WaiterController extends Controller
{
    /**
     * Display waiter panel with dine-in orders and tables
     */
    public function index()
    {
        // Kelner widzi wszystkie zamówienia od złożenia aż do zakończenia
        $orders = Order::with(['items.product', 'items.variant', 'table'])
            ->whereIn('status', ['pending', 'paid', 'preparing', 'ready', 'on_delivery'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Pobierz wszystkie stoliki
        $tables = Table::where('is_active', true)
            ->orderBy('number', 'asc')
            ->get();

        // Pobierz kategorie z produktami dla formularza nowego zamówienia
        $categories = \App\Models\Tenant\Category::with(['products' => function ($q) {
                $q->where('is_available', true)->with(['variants', 'addons' => function ($q) {
                    $q->where('is_available', true);
                }]);
            }])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Tenant/Staff/Waiter', [
            'orders'     => $orders,
            'tables'     => $tables,
            'categories' => $categories,
        ]);
    }

    /**
     * Accept a pending order (pending → paid)
     */
    public function acceptOrder(Order $order)
    {
        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Zamówienie nie jest w statusie oczekującym.'], 422);
        }

        $oldStatus = $order->status;
        $order->update(['status' => 'paid']);

        Log::info('Waiter: zamówienie przyjęte', [
            'order_number' => $order->order_number,
            'operator_id'  => auth('tenant')->id(),
        ]);

        event(new OrderStatusChanged($order, $oldStatus, 'paid'));

        return response()->json(['success' => true]);
    }

    /**
     * Mark dine_in / pickup order as completed by waiter
     */
    public function completeOrder(Order $order)
    {
        if ($order->type === 'delivery') {
            return response()->json(['message' => 'Zamówień dostawowych nie może zakończyć kelner.'], 403);
        }

        $oldStatus = $order->status;
        $updateData = ['status' => 'completed'];

        if (in_array($order->payment_method, ['cash', 'card_on_delivery']) && $order->payment_status !== 'paid') {
            $updateData['payment_status'] = 'paid';
        }

        $order->update($updateData);

        Log::info('Waiter: zamówienie zakończone', [
            'order_number' => $order->order_number,
            'type'         => $order->type,
            'operator_id'  => auth('tenant')->id(),
        ]);

        event(new OrderStatusChanged($order, $oldStatus, 'completed'));

        return response()->json(['success' => true]);
    }

    /**
     * Display tables with QR codes for management
     */
    public function tables()
    {
        $tables = Table::orderBy('number', 'asc')->get();

        return Inertia::render('Tenant/Staff/Tables', [
            'tables' => $tables,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'number'      => 'required|integer|min:1',
            'name'        => 'nullable|string|max:50',
            'seats'       => 'required|integer|min:1|max:50',
            'min_guests'  => 'nullable|integer|min:1|max:50',
            'location'    => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        Table::create($validated);

        return back()->with('success', 'Stolik został dodany.');
    }

    public function update(Request $request, Table $table)
    {
        $validated = $request->validate([
            'number'      => 'required|integer|min:1',
            'name'        => 'nullable|string|max:50',
            'seats'       => 'required|integer|min:1|max:50',
            'min_guests'  => 'nullable|integer|min:1|max:50',
            'location'    => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $table->update($validated);

        return back()->with('success', 'Stolik został zaktualizowany.');
    }

    public function destroy(Table $table)
    {
        $table->delete();

        return back()->with('success', 'Stolik został usunięty.');
    }

    public function toggle(Table $table)
    {
        $table->update(['is_active' => !$table->is_active]);

        return back()->with('success', 'Status stolika został zmieniony.');
    }

    public function setStatus(Request $request, Table $table)
    {
        $request->validate([
            'status' => 'required|in:available,occupied,reserved,cleaning',
        ]);

        $table->update(['status' => $request->status]);

        return response()->json(['success' => true, 'status' => $table->status]);
    }

    public function generateQrCode(Table $table)
    {
        $menuUrl    = url('/') . '?table=' . $table->id;
        $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($menuUrl);

        $table->update([
            'qr_code'      => $menuUrl,
            'qr_image_url' => $qrImageUrl,
        ]);

        return response()->json([
            'success'      => true,
            'qr_image_url' => $qrImageUrl,
            'menu_url'     => $menuUrl,
        ]);
    }
}
