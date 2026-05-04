<?php

namespace App\Http\Controllers\Tenant\Staff;

use App\Http\Controllers\Controller;
use App\Helpers\PhoneHelper;
use App\Events\OrderCreated;
use App\Models\Tenant\DiscountCode;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PosController extends Controller
{
    public function index()
    {
        $categories = Category::with(['products.variants', 'products.addons'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $tables = \App\Models\Tenant\Table::where('is_active', true)
            ->orderBy('number')
            ->get(['id', 'number', 'name']);

        return Inertia::render('Tenant/Staff/Pos', [
            'categories' => $categories,
            'tables'     => $tables,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'             => ['required', 'in:delivery,pickup,dine_in'],
            'customer_name'    => ['required', 'string', 'max:255'],
            'customer_phone'   => ['nullable', 'string', 'max:30'],
            'delivery_address' => ['required_if:type,delivery', 'nullable', 'string'],
            'payment_method'   => ['required', 'in:cash,card_on_delivery'],
            'payment_status'   => ['nullable', 'in:pending,paid'],
            'notes'            => ['nullable', 'string', 'max:500'],
            'table_id'         => ['nullable', 'integer', 'exists:tables,id'],
            'discount_code'    => ['nullable', 'string', 'max:50'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.product_id'   => ['required', 'exists:products,id'],
            'items.*.variant_id'   => ['required', 'exists:product_variants,id'],
            'items.*.quantity'     => ['required', 'integer', 'min:1'],
            'items.*.addons'       => ['nullable', 'array'],
            'items.*.exclusions'   => ['nullable', 'array'],
            'items.*.notes'        => ['nullable', 'string', 'max:200'],
        ]);

        if (!empty($validated['customer_phone'])) {
            $validated['customer_phone'] = PhoneHelper::normalize($validated['customer_phone']) ?? $validated['customer_phone'];
        }

        try {
            DB::beginTransaction();

            $subtotal = 0;
            $orderItems = [];

            foreach ($validated['items'] as $item) {
                $product = Product::with('variants')->findOrFail($item['product_id']);
                $variant = $product->variants->firstWhere('id', $item['variant_id']);

                if (!$variant) {
                    throw new \Exception('Nieprawidłowy wariant produktu');
                }

                $itemPrice = $variant->price;
                $addonsData = [];

                if (!empty($item['addons'])) {
                    foreach ($item['addons'] as $addonId) {
                        $addon = \App\Models\Tenant\Addon::findOrFail($addonId);
                        $itemPrice += $addon->price;
                        $addonsData[] = ['id' => $addon->id, 'name' => $addon->name, 'price' => $addon->price];
                    }
                }

                $lineTotal = $itemPrice * $item['quantity'];
                $subtotal += $lineTotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'name'       => $product->name,
                    'variant_name' => $variant->name,
                    'price'      => $itemPrice,
                    'quantity'   => $item['quantity'],
                    'addons'     => $addonsData ?: null,
                    'exclusions' => $item['exclusions'] ?? null,
                    'notes'      => $item['notes'] ?? null,
                ];
            }

            // Apply discount code
            $discount = 0;
            $discountCode = null;
            if (!empty($validated['discount_code'])) {
                $discountCode = DiscountCode::where('code', strtoupper($validated['discount_code']))
                    ->valid()
                    ->notExhausted()
                    ->first();

                if (!$discountCode) {
                    throw new \Exception('Nieprawidłowy lub nieaktywny kod rabatowy.');
                }

                $check = $discountCode->canBeUsed($subtotal);
                if (!$check['valid']) {
                    throw new \Exception($check['message']);
                }

                $discount = $discountCode->type === 'percentage'
                    ? round($subtotal * $discountCode->value / 100, 2)
                    : min((float) $discountCode->value, $subtotal);

                $discountCode->increment('used_count');
            }

            $total = max(0, $subtotal - $discount);

            $order = Order::create([
                'type'             => $validated['type'],
                'customer_name'    => $validated['customer_name'],
                'customer_email'   => null,
                'customer_phone'   => $validated['customer_phone'] ?? null,
                'delivery_address' => $validated['delivery_address'] ?? null,
                'table_id'         => $validated['table_id'] ?? null,
                'subtotal'         => $subtotal,
                'delivery_fee'     => 0,
                'discount'         => $discount,
                'total'            => $total,
                'payment_method'   => $validated['payment_method'],
                'payment_status'   => $validated['payment_status'] ?? 'pending',
                'status'           => 'paid',
                'notes'            => $validated['notes'] ?? null,
                'source'           => 'pos',
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            DB::commit();

            Log::info('POS: zamówienie złożone', [
                'order_number'   => $order->order_number,
                'type'           => $order->type,
                'operator_id'    => Auth::guard('tenant')->id(),
                'total'          => $order->total,
                'payment_method' => $order->payment_method,
                'items_count'    => count($orderItems),
                'discount'       => $order->discount,
            ]);

            event(new OrderCreated($order));

            if (request()->expectsJson()) {
                return response()->json(['success' => true, 'order_number' => $order->order_number]);
            }

            return back()->with('success', "Zamówienie #{$order->order_number} zostało przyjęte.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS: błąd przy tworzeniu zamówienia', ['error' => $e->getMessage(), 'operator_id' => Auth::guard('tenant')->id()]);

            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['message' => 'Błąd: ' . $e->getMessage()]);
        }
    }
}
