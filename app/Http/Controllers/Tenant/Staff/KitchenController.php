<?php

namespace App\Http\Controllers\Tenant\Staff;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Order;
use Inertia\Inertia;

class KitchenController extends Controller
{
    public function index()
    {
        // Kuchnia widzi zamówienia dopiero po statusie "accepted" (przyjęte przez managera)
        $orders = Order::with(['items.product', 'items.variant', 'deliveryZone', 'table'])
            ->whereIn('status', ['paid', 'preparing'])
            ->orderBy('created_at', 'asc')
            ->get();

        return Inertia::render('Tenant/Staff/Kitchen', [
            'orders' => $orders,
        ]);
    }
}
