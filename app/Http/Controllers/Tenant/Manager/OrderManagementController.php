<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Category;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\Table;
use App\Events\OrderStatusChanged;
use App\Mail\Tenant\OrderStatusChangedMail;
use App\Services\LoyaltyService;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class OrderManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['items', 'customer', 'discountCode', 'table'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        $categories = Category::with(['products' => function ($q) {
                $q->where('is_available', true)->with(['variants', 'addons' => function ($q) {
                    $q->where('is_available', true);
                }]);
            }])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $tables = Table::where('is_active', true)->orderBy('number')->get();

        return Inertia::render('Tenant/Manager/Orders/Index', [
            'orders'     => $orders,
            'filters'    => $request->only(['status', 'type', 'payment_status', 'search']),
            'categories' => $categories,
            'tables'     => $tables,
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items']);

        return Inertia::render('Tenant/Manager/Orders/Show', [
            'order' => $order,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,paid,preparing,ready,on_delivery,completed,cancelled',
        ]);

        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        $updateData = ['status' => $newStatus];
        // Auto-mark payment as paid for cash/card-on-delivery when order completed
        if ($newStatus === 'completed'
            && in_array($order->payment_method, ['cash', 'card_on_delivery'])
            && $order->payment_status !== 'paid') {
            $updateData['payment_status'] = 'paid';
        }
        $order->update($updateData);

        Log::info('Order: zmiana statusu', [
            'order_number'    => $order->order_number,
            'old_status'      => $oldStatus,
            'new_status'      => $newStatus,
            'payment_status'  => $updateData['payment_status'] ?? $order->payment_status,
            'manager_id'      => auth('tenant')->id(),
        ]);

        // Broadcast WebSocket event
        event(new OrderStatusChanged($order, $oldStatus, $newStatus));

        // Send email notification only for completed and cancelled statuses
        if ($order->customer_email && in_array($newStatus, ['completed', 'cancelled'])) {
            try {
                $order->load('items');
                $token = $order->tracking_token ?? \App\Http\Controllers\Tenant\Client\OrderTrackingController::generateToken($order->order_number);
                $trackingUrl = route('tenant.order.tracking', $order->order_number) . '?token=' . $token;
                Mail::to($order->customer_email)->queue(new OrderStatusChangedMail($order, $oldStatus, $newStatus, $trackingUrl));
            } catch (\Exception $e) {
                Log::warning('Order status email failed: ' . $e->getMessage());
            }
        }

        // Send SMS notification
        if ($order->customer_phone && $newStatus !== 'pending') {
            try {
                app(SmsService::class)->sendOrderStatus($order->customer_phone, $order->order_number, $newStatus);
            } catch (\Exception $e) {
                Log::warning('Order status SMS failed: ' . $e->getMessage());
            }
        }

        // Award loyalty points when order completed
        if ($newStatus === 'completed' && $order->customer_id) {
            try {
                $loyaltyService = app(LoyaltyService::class);
                $loyaltyService->awardPointsForOrder($order);

                // Award referral bonus to referrer on customer's first completed order
                $customer = $order->customer;
                if ($customer && $customer->loyalty_referred_by && !$customer->referral_bonus_awarded) {
                    $referrer = \App\Models\Tenant\Customer::find($customer->loyalty_referred_by);
                    if ($referrer) {
                        $loyaltyService->awardReferralBonus($referrer);
                        $customer->update(['referral_bonus_awarded' => true]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Loyalty points award failed: ' . $e->getMessage());
            }
        }

        // When cancelled: attempt payment refund if order was paid online
        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled'
            && $order->payment_status === 'paid'
            && in_array($order->payment_method, ['przelewy24', 'payu', 'tpay', 'stripe', 'online'])
        ) {
            try {
                $gateway = PaymentGatewayFactory::make($order->payment_method);
                if (method_exists($gateway, 'refund')) {
                    $result = $gateway->refund($order);
                    if ($result['success']) {
                        $order->update(['payment_status' => 'refunded']);
                        Log::info('Order: zwrot płatności pomyślny', ['order_number' => $order->order_number, 'method' => $order->payment_method]);
                    } else {
                        $order->update(['payment_status' => 'refund_failed']);
                        Log::warning('Refund failed for order ' . $order->order_number . ': ' . ($result['error'] ?? ''));
                    }
                }
            } catch (\Exception $e) {
                Log::error('Refund exception for order ' . $order->order_number . ': ' . $e->getMessage());
            }
        }

        // When cancelled: restore stock and revoke loyalty points
        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            try {
                $order->load('items');
                foreach ($order->items as $item) {
                    $product = Product::find($item->product_id);
                    if ($product && $product->track_stock) {
                        $product->increment('stock_quantity', $item->quantity);
                        Log::info('Order: przywrócono stan magazynowy', [
                            'order_number' => $order->order_number,
                            'product_id'   => $product->id,
                            'product_name' => $product->name,
                            'qty'          => $item->quantity,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Stock restore failed: ' . $e->getMessage());
            }

            if ($order->customer_id) {
                try {
                    app(LoyaltyService::class)->revokePointsForOrder($order);
                } catch (\Exception $e) {
                    Log::warning('Loyalty revoke failed: ' . $e->getMessage());
                }
            }
        }

        return back()->with('success', 'Status zamówienia został zaktualizowany');
    }

    public function updatePaymentStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_status' => 'required|in:pending,paid,failed,refunded,refund_failed',
        ]);

        $old = $order->payment_status;
        $order->update(['payment_status' => $validated['payment_status']]);

        Log::info('Order: zmiana statusu płatności', [
            'order_number'       => $order->order_number,
            'old_payment_status' => $old,
            'new_payment_status' => $validated['payment_status'],
            'manager_id'         => auth('tenant')->id(),
        ]);

        return back()->with('success', 'Status płatności został zaktualizowany.');
    }
}
