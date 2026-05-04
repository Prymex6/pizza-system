<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Helpers\PhoneHelper;
use App\Models\Tenant\Order;
use App\Models\Tenant\DiscountCode;
use App\Models\Tenant\LoyaltyReward;
use App\Models\Tenant\Setting;
use App\Services\Geolocation\DeliveryZoneService;
use App\Services\LoyaltyService;
use App\Services\Payment\PaymentGatewayFactory;
use App\Events\OrderCreated;
use App\Mail\Tenant\NewOrderNotificationMail;
use App\Mail\Tenant\OrderConfirmedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function __construct(
        protected DeliveryZoneService $deliveryZoneService,
        protected LoyaltyService $loyaltyService
    ) {}

    public function index()
    {
        // Blokuj checkout gdy restauracja zamknięta
        if (Setting::get('vacation_mode', false)) {
            return redirect()->route('tenant.menu')->with('info', Setting::get('vacation_message', 'Restauracja jest chwilowo niedostępna.'));
        }
        if (Setting::get('orders_paused', false)) {
            return redirect()->route('tenant.menu')->with('info', 'Zamówienia są chwilowo wstrzymane.');
        }

        // Get all active delivery zones for map display
        $deliveryZones = $this->deliveryZoneService->getAllActiveZones();

        // Build available payment methods based on settings
        $paymentMethods = [];

        if (Setting::get('payment_cash_enabled', true)) {
            $paymentMethods[] = ['value' => 'cash', 'label' => 'Gotówka przy odbiorze', 'icon' => 'fa-money-bill-wave', 'type' => 'offline'];
        }
        if (Setting::get('payment_card_on_delivery_enabled', false)) {
            $paymentMethods[] = ['value' => 'card_on_delivery', 'label' => 'Karta przy odbiorze', 'icon' => 'fa-credit-card', 'type' => 'offline'];
        }

        // Online gateways - only show configured ones
        $configuredGateways = PaymentGatewayFactory::configuredGateways();
        foreach ($configuredGateways as $method => $meta) {
            $settingKey = $meta['setting_key'] ?? "payment_{$method}_enabled";
            if (Setting::get($settingKey, false)) {
                $paymentMethods[] = [
                    'value'       => $method,
                    'label'       => $meta['label'],
                    'description' => $meta['description'],
                    'icon'        => $meta['logo'],
                    'color'       => $meta['color'],
                    'type'        => 'online',
                ];
            }
        }

        $loyaltyOptions = null;
        if (Setting::get('loyalty_enabled', false) && Auth::guard('customer')->check()) {
            $customer = Auth::guard('customer')->user();
            $loyaltyOptions = $this->loyaltyService->calculateCheckoutOptions($customer, 0);
        }

        return Inertia::render('Tenant/Client/Checkout', [
            'deliveryZones'        => $deliveryZones,
            'googleMapsConfigured' => $this->deliveryZoneService->isConfigured(),
            'paymentMethods'       => $paymentMethods,
            'loyaltyOptions'       => $loyaltyOptions,
        ]);
    }

    /**
     * Validate cart items against current DB state (AJAX endpoint).
     * Returns list of invalid items (deleted or unavailable products/variants).
     */
    public function validateCart(Request $request)
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variant_id' => ['required', 'integer'],
        ]);

        $invalidItems = [];

        foreach ($request->input('items') as $item) {
            $product = \App\Models\Tenant\Product::find($item['product_id']);

            if (!$product) {
                $invalidItems[] = [
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'reason' => 'not_found',
                ];
                continue;
            }

            if (!$product->is_available) {
                $invalidItems[] = [
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'name' => $product->name,
                    'reason' => 'unavailable',
                ];
                continue;
            }

            $variant = \App\Models\Tenant\ProductVariant::where('id', $item['variant_id'])
                ->where('product_id', $product->id)
                ->first();

            if (!$variant) {
                $invalidItems[] = [
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'name' => $product->name,
                    'reason' => 'variant_not_found',
                ];
            }
        }

        return response()->json([
            'valid' => empty($invalidItems),
            'invalid_items' => $invalidItems,
        ]);
    }

    /**
     * Validate delivery address (AJAX endpoint)
     */
    public function validateAddress(Request $request)
    {
        $request->validate([
            'address' => ['required', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $validationResult = $this->deliveryZoneService->validateDeliveryAddress(
            $request->address,
            $request->subtotal
        );

        if (!$validationResult['valid']) {
            return response()->json([
                'valid' => false,
                'error' => $validationResult['error'],
            ]);
        }

        $zone = $validationResult['zone'];

        return response()->json([
            'valid' => true,
            'zone' => $zone ? [
                'id' => $zone->id,
                'name' => $zone->name,
                'delivery_fee' => $zone->delivery_fee,
                'min_order_value' => $zone->min_order_value,
                'estimated_time' => $zone->estimated_time,
                'delivery_info' => $zone->delivery_info,
            ] : null,
            'coordinates' => $validationResult['coordinates'],
        ]);
    }

    /**
     * Validate discount code (AJAX endpoint)
     */
    public function validateDiscountCode(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $code = strtoupper(trim($request->code));

        $discountCode = DiscountCode::where('code', $code)->first();

        if (!$discountCode || !$discountCode->is_active) {
            return response()->json([
                'valid' => false,
                'message' => 'Kod rabatowy nie istnieje lub jest nieaktywny',
            ], 422);
        }

        if ($discountCode->max_uses && $discountCode->used_count >= $discountCode->max_uses) {
            return response()->json([
                'valid' => false,
                'message' => 'Kod rabatowy został już wykorzystany maksymalną liczbę razy',
            ], 422);
        }

        $validation = $discountCode->canBeUsed($request->subtotal);

        if (!$validation['valid']) {
            return response()->json([
                'valid' => false,
                'message' => $validation['message'],
            ], 422);
        }

        $discountAmount = $discountCode->calculateDiscount($request->subtotal);

        return response()->json([
            'valid' => true,
            'message' => 'Kod rabatowy został zastosowany!',
            'discount' => [
                'id' => $discountCode->id,
                'code' => $discountCode->code,
                'type' => $discountCode->type,
                'value' => $discountCode->value,
                'amount' => $discountAmount,
                'formatted_amount' => number_format($discountAmount, 2, ',', ' ') . ' PLN',
            ],
        ]);
    }

    public function store(Request $request)
    {
        // Early business-rule checks (before full validation with DB exists checks)
        $type = $request->input('type');
        $subtotal = (float) $request->input('subtotal', 0);

        if ($type === 'delivery' && !Setting::get('delivery_enabled', true)) {
            return response()->json(['success' => false, 'message' => 'Dostawa jest obecnie niedostępna.'], 422);
        }
        if ($type === 'pickup' && !Setting::get('pickup_enabled', true)) {
            return response()->json(['success' => false, 'message' => 'Odbiór osobisty jest obecnie niedostępny.'], 422);
        }

        $hasFreeProductReward = !empty($request->input('loyalty_reward_id'))
            && LoyaltyReward::where('id', $request->input('loyalty_reward_id'))->where('type', 'free_product')->exists();

        $minOrderValue = (float) Setting::get('min_order_value', 0);
        if ($minOrderValue > 0 && $subtotal < $minOrderValue && $type === 'delivery' && !$hasFreeProductReward) {
            return response()->json(['success' => false, 'message' => "Minimalna wartość zamówienia to {$minOrderValue} zł."], 422);
        }

        $validated = $request->validate([
            'type' => ['required', 'in:delivery,pickup,dine_in'],
            'terms_accepted' => ['required', 'accepted'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'delivery_address' => ['required_if:type,delivery', 'nullable', 'string'],
            'payment_method' => ['required', 'in:przelewy24,payu,tpay,stripe,cash,card_on_delivery,online'],
            'notes' => ['nullable', 'string', 'max:500'],
            'discount_code'       => ['nullable', 'string'],
            'loyalty_reward_id'   => ['nullable', 'integer', 'exists:loyalty_rewards,id'],
            'loyalty_points_redeem' => ['nullable', 'integer', 'min:1', 'max:' . (Auth::guard('customer')->user()?->loyalty_points ?? 0)],
            'items' => [$hasFreeProductReward ? 'present' : 'required', 'array', $hasFreeProductReward ? 'min:0' : 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.addons' => ['nullable', 'array'],
            'items.*.exclusions' => ['nullable', 'array'],
            'items.*.notes' => ['nullable', 'string', 'max:200'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'table_id' => ['nullable', 'integer', 'exists:tables,id'],
        ]);

        $validated['customer_phone'] = PhoneHelper::normalize($validated['customer_phone']) ?? $validated['customer_phone'];

        // Validate loyalty reward availability before entering transaction
        $customer = Auth::guard('customer')->user();
        if ($customer && Setting::get('loyalty_enabled', false) && !empty($validated['loyalty_reward_id'])) {
            $reward = LoyaltyReward::find($validated['loyalty_reward_id']);
            if (!$reward || !$reward->isAvailable()) {
                return back()->withErrors(['loyalty_reward_id' => 'Wybrana nagroda jest niedostępna.']);
            }
            if ($customer->loyalty_points < $reward->cost_points) {
                return back()->withErrors(['loyalty_reward_id' => 'Niewystarczająca liczba punktów lojalnościowych.']);
            }
        }

        try {
            DB::beginTransaction();

            // Calculate subtotal
            $subtotal = 0;
            $orderItems = [];

            foreach ($validated['items'] as $item) {
                // lockForUpdate ensures no concurrent request reads stale stock
                $product = \App\Models\Tenant\Product::lockForUpdate()->findOrFail($item['product_id']);
                $variant = \App\Models\Tenant\ProductVariant::where('id', $item['variant_id'])
                    ->where('product_id', $product->id)
                    ->first();

                if (!$variant) {
                    throw new \Exception('Nieprawidłowy wariant produktu.');
                }

                if (!$product->is_available) {
                    throw new \Exception("Produkt '{$product->name}' jest niedostępny.");
                }

                // Inventory check (safe — row is locked)
                if ($product->track_stock) {
                    if ($product->stock_quantity <= 0) {
                        throw new \Exception("Produkt '{$product->name}' jest niedostępny (brak w magazynie).");
                    }
                    if ($product->stock_quantity < $item['quantity']) {
                        throw new \Exception("Produkt '{$product->name}' dostępny tylko w ilości {$product->stock_quantity} szt.");
                    }
                }

                $itemPrice = $variant->price;
                $addonsData = [];

                if (!empty($item['addons'])) {
                    foreach ($item['addons'] as $addonId) {
                        $addon = \App\Models\Tenant\Addon::findOrFail($addonId);
                        $itemPrice += $addon->price;
                        $addonsData[] = [
                            'id' => $addon->id,
                            'name' => $addon->name,
                            'price' => $addon->price,
                        ];
                    }
                }

                $lineTotal = $itemPrice * $item['quantity'];
                $subtotal += $lineTotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'name' => $product->name,
                    'variant_name' => $variant->name,
                    'price' => $itemPrice,
                    'quantity' => $item['quantity'],
                    'addons' => $addonsData,
                    'exclusions' => $item['exclusions'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ];
            }

            // Validate delivery address and calculate delivery fee
            $deliveryFee = 0;
            $deliveryZoneId = null;
            $deliveryCoordinates = null;

            if ($validated['type'] === 'delivery') {
                $fullAddress = $validated['delivery_address'];

                $validationResult = $this->deliveryZoneService->validateDeliveryAddress(
                    $fullAddress,
                    $subtotal
                );

                if (!$validationResult['valid']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'delivery_zone_error' => true,
                        'message' => $validationResult['error'],
                    ], 422);
                }

                $zone = $validationResult['zone'];
                $deliveryFee = $zone ? $this->deliveryZoneService->calculateDeliveryFee($zone) : 0;
                $deliveryZoneId = $zone?->id;
                $deliveryCoordinates = $validationResult['coordinates'];
            }

            // Process discount code
            $discount = 0;
            $discountCodeId = null;

            if (!empty($validated['discount_code'])) {
                $code = strtoupper(trim($validated['discount_code']));

                // lockForUpdate prevents double-use when max_uses is reached concurrently
                $discountCode = DiscountCode::where('code', $code)
                    ->active()
                    ->valid()
                    ->notExhausted()
                    ->lockForUpdate()
                    ->first();

                if ($discountCode) {
                    $validation = $discountCode->canBeUsed($subtotal);

                    if ($validation['valid']) {
                        $discount = $discountCode->calculateDiscount($subtotal);
                        $discountCodeId = $discountCode->id;
                    }
                }
            }

            // Process loyalty redemption (reward or points-to-PLN)
            $loyaltyDiscount   = 0;
            $loyaltyRewardId   = null;
            $loyaltyRewardType = null;
            $loyaltyPointsSpent = 0;
            // $customer already set above (pre-transaction validation)

            if ($customer && Setting::get('loyalty_enabled', false)) {
                if (!empty($validated['loyalty_reward_id'])) {
                    $reward = LoyaltyReward::with(['product', 'productVariant'])->find($validated['loyalty_reward_id']);
                    if ($reward && $reward->isAvailable() && $customer->loyalty_points >= $reward->cost_points) {
                        $loyaltyRewardId    = $reward->id;
                        $loyaltyRewardType  = $reward->type;
                        $loyaltyPointsSpent = $reward->cost_points;
                        $loyaltyDiscount    = match ($reward->type) {
                            'fixed_discount'   => min((float)$reward->value, $subtotal),
                            'percent_discount' => round($subtotal * ($reward->value / 100), 2),
                            'free_delivery'    => $deliveryFee,
                            default            => 0,
                        };

                        // Add free product to order items at price 0
                        if ($reward->type === 'free_product' && $reward->product) {
                            $freeVariant = $reward->productVariant ?? $reward->product->variants()->orderBy('sort_order')->first();
                            if ($freeVariant) {
                                $orderItems[] = [
                                    'product_id'   => $reward->product->id,
                                    'variant_id'   => $freeVariant->id,
                                    'name'         => $reward->product->name,
                                    'variant_name' => $freeVariant->name,
                                    'price'        => 0,
                                    'quantity'     => 1,
                                    'addons'       => [],
                                    'exclusions'   => null,
                                    'notes'        => 'Nagroda lojalnościowa',
                                ];
                            }
                        }
                    }
                } elseif (!empty($validated['loyalty_points_redeem'])) {
                    $ptsToRedeem    = (int)$validated['loyalty_points_redeem'];
                    $ptsPerPln      = max(1, (int)Setting::get('loyalty_points_per_pln', 1));
                    $maxFromBalance = $customer->loyalty_points;
                    $ptsToRedeem    = min($ptsToRedeem, $maxFromBalance);
                    $loyaltyDiscount    = min(floor($ptsToRedeem / $ptsPerPln), $subtotal);
                    $loyaltyPointsSpent = $loyaltyDiscount * $ptsPerPln;
                    $loyaltyRewardType  = 'points_pln';
                }
            }

            $total = max(0, $subtotal + $deliveryFee - $discount - $loyaltyDiscount);

            // Calculate estimated delivery time once at order creation
            $prepMinutes = (int) Setting::get('estimated_preparation_time', 30);
            $zone = $deliveryZoneId ? \App\Models\Tenant\DeliveryZone::find($deliveryZoneId) : null;
            if ($validated['type'] === 'delivery' && $zone) {
                $prepMinutes += $zone->estimated_time ?? 15;
            }
            $estimatedDeliveryAt = now()->addMinutes($prepMinutes);

            // Resolve table for dine_in orders
            $tableId = null;
            if ($validated['type'] === 'dine_in' && !empty($validated['table_id'])) {
                $table = \App\Models\Tenant\Table::where('id', $validated['table_id'])->where('is_active', true)->first();
                $tableId = $table?->id;
            }

            // Create order
            $order = Order::create([
                'type' => $validated['type'],
                'table_id' => $tableId,
                'tracking_token' => \Illuminate\Support\Str::random(32),
                'customer_id' => Auth::guard('customer')->id(),
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'delivery_address' => $validated['delivery_address'] ?? null,
                'delivery_zone_id' => $deliveryZoneId,
                'delivery_coordinates' => $deliveryCoordinates,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'discount' => $discount + $loyaltyDiscount,
                'total' => $total,
                'payment_method' => $validated['payment_method'],
                'payment_status' => in_array($validated['payment_method'], ['przelewy24', 'payu', 'tpay', 'stripe', 'online']) ? 'awaiting_payment' : 'pending',
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'discount_code_id' => $discountCodeId,
                'estimated_delivery_time' => $estimatedDeliveryAt,
            ]);

            // Create order items
            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            // Decrement stock for tracked products
            foreach ($validated['items'] as $item) {
                $product = \App\Models\Tenant\Product::find($item['product_id']);
                if ($product && $product->track_stock) {
                    $product->decrement('stock_quantity', $item['quantity']);
                }
            }
            // Also decrement stock for free product reward
            if ($loyaltyRewardId) {
                $freeReward = \App\Models\Tenant\LoyaltyReward::find($loyaltyRewardId);
                if ($freeReward && $freeReward->type === 'free_product' && $freeReward->product && $freeReward->product->track_stock) {
                    $freeReward->product->decrement('stock_quantity', 1);
                }
            }

            // Increment discount code usage
            if ($discountCodeId) {
                $discountCode->incrementUsage();
                Log::info('Checkout: kod rabatowy użyty', [
                    'order_number' => $order->order_number,
                    'code'         => $discountCode->code,
                    'discount'     => $discount,
                ]);
            }

            // Record loyalty redemption (deduct points)
            if ($customer && $loyaltyPointsSpent > 0) {
                if ($loyaltyRewardId) {
                    $reward = LoyaltyReward::find($loyaltyRewardId);
                    $this->loyaltyService->redeemReward($customer, $reward, $order);
                } elseif ($loyaltyRewardType === 'points_pln') {
                    $this->loyaltyService->redeemPointsAsPln($customer, (int)$loyaltyPointsSpent, $order);
                }
            }

            DB::commit();

            Log::info('Checkout: zamówienie złożone', [
                'order_number'    => $order->order_number,
                'type'            => $order->type,
                'customer_id'     => $order->customer_id,
                'customer_email'  => $order->customer_email,
                'total'           => $order->total,
                'payment_method'  => $order->payment_method,
                'items_count'     => count($orderItems),
                'discount'        => $order->discount,
                'loyalty_spent'   => $loyaltyPointsSpent,
            ]);

            $isOnlinePayment = in_array($validated['payment_method'], ['przelewy24', 'payu', 'tpay', 'stripe', 'online']);

            // Broadcast order created event (only for non-online payments; online fires after payment confirmation)
            if (!$isOnlinePayment) {
                event(new OrderCreated($order));
            }

            // Send confirmation email to customer
            if (!empty($validated['customer_email'])) {
                try {
                    $order->load('items');
                    $trackingUrl = route('tenant.order.tracking', $order->order_number) . '?token=' . $order->tracking_token;
                    $timezone = Setting::get('timezone', 'Europe/Warsaw') ?: 'Europe/Warsaw';
                    $estimatedTimeFormatted = $order->estimated_delivery_time
                        ? \Carbon\Carbon::createFromTimestamp($order->estimated_delivery_time->timestamp, $timezone)->format('H:i')
                        : null;
                    Mail::to($validated['customer_email'])->queue(new OrderConfirmedMail($order, $trackingUrl, $estimatedTimeFormatted));
                } catch (\Exception $e) {
                    Log::warning('Order confirmation email failed: ' . $e->getMessage());
                }
            }

            // Notify restaurant owner about new order
            $restaurantEmail = Setting::get('restaurant_email');
            if ($restaurantEmail) {
                try {
                    $order->loadMissing('items');
                    Mail::to($restaurantEmail)->queue(new NewOrderNotificationMail($order));
                } catch (\Exception $e) {
                    Log::warning('New order notification email failed: ' . $e->getMessage());
                }
            }

            // If online payment, redirect to payment gateway
            if ($isOnlinePayment) {
                return response()->json([
                    'success' => true,
                    'order_number' => $order->order_number,
                    'redirect_to_payment' => true,
                    'payment_url' => route('tenant.payment.initiate', $order),
                ]);
            }

            return response()->json([
                'success' => true,
                'order_number' => $order->order_number,
                'tracking_token' => $order->tracking_token,
                'message' => 'Zamówienie zostało przyjęte!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Wystąpił błąd podczas składania zamówienia: ' . $e->getMessage(),
            ], 422);
        }
    }
}
