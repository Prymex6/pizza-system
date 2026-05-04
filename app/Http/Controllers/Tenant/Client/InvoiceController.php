<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Order;
use App\Models\Tenant\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * Manager-accessible invoice (test version only).
     * Route: GET /manager/orders/{orderNumber}/faktura
     */
    public function showForManager(Request $request, string $orderNumber)
    {
        if ((tenancy()->tenant?->version ?? 'stable') !== 'test') {
            abort(403, 'Faktury VAT dostępne tylko w wersji testowej systemu.');
        }

        $order = Order::with('items')
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return $this->renderInvoice($request, $order);
    }

    /**
     * Customer-accessible invoice.
     * Route: GET /zamowienia/{orderNumber}/faktura (auth.customer)
     */
    public function show(Request $request, string $orderNumber)
    {
        $customer = Auth::guard('customer')->user();

        $order = Order::with('items')
            ->where('order_number', $orderNumber)
            ->when($customer, fn($q) => $q->where('customer_id', $customer->id))
            ->firstOrFail();

        return $this->renderInvoice($request, $order);
    }

    private function renderInvoice(Request $request, Order $order): \Illuminate\Contracts\View\View
    {
        // Generate FV number if not already assigned
        if (!$order->invoice_number) {
            $invoiceNumber = DB::transaction(function () use ($order) {
                $year = $order->created_at->format('Y');
                $prefix = 'FV/' . $year . '/';
                $last = DB::table('orders')
                    ->where('invoice_number', 'like', $prefix . '%')
                    ->lockForUpdate()
                    ->max('invoice_number');
                $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;
                return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
            });
            $order->update(['invoice_number' => $invoiceNumber]);
        }

        // Handle optional buyer NIP from query param
        $buyerNip = $request->query('nip');
        if ($buyerNip && !$order->buyer_nip) {
            $order->update(['buyer_nip' => preg_replace('/[^0-9\-]/', '', $buyerNip)]);
            $order->refresh();
        }

        $settings = Setting::getAllAsArray();

        return view('pdf.invoice', [
            'order'     => $order,
            'settings'  => $settings,
            'vatGroups' => $this->computeVat($order),
        ]);
    }

    private function computeVat(Order $order): array
    {
        $net8 = 0; $vat8 = 0;
        $net23 = 0; $vat23 = 0;

        foreach ($order->items as $item) {
            $gross = round($item->price * $item->quantity, 2);
            $net   = round($gross / 1.08, 2);
            $vat8  += round($gross - $net, 2);
            $net8  += $net;
        }

        if ($order->delivery_fee > 0) {
            $gross = round($order->delivery_fee, 2);
            $net   = round($gross / 1.23, 2);
            $vat23 += round($gross - $net, 2);
            $net23 += $net;
        }

        return [
            ['rate' => '8%',  'net' => $net8,  'vat' => $vat8,  'gross' => $net8 + $vat8],
            ['rate' => '23%', 'net' => $net23, 'vat' => $vat23, 'gross' => $net23 + $vat23],
        ];
    }
}
