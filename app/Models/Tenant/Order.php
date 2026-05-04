<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'tracking_token',
        'type',
        'table_id',
        'status',
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'delivery_address',
        'delivery_city',
        'delivery_postal_code',
        'delivery_zone_id',
        'delivery_coordinates',
        'subtotal',
        'delivery_fee',
        'discount',
        'discount_code_id',
        'total',
        'payment_method',
        'payment_status',
        'payment_data',
        'paid_at',
        'notes',
        'estimated_delivery_time',
        'driver_id',
        'invoice_number',
        'buyer_nip',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'estimated_delivery_time' => 'datetime',
        'payment_data' => 'array',
        'delivery_coordinates' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = DB::transaction(function () {
                    $tz    = \App\Models\Tenant\Setting::get('timezone', 'Europe/Warsaw');
                    $today = now()->setTimezone($tz)->format('ymd');   // e.g. 260228
                    $prefix = 'ORD-' . $today . '-';

                    // Find highest sequential number for today
                    $last = DB::table('orders')
                        ->where('order_number', 'like', $prefix . '%')
                        ->lockForUpdate()
                        ->max('order_number');

                    $seq = $last
                        ? ((int) substr($last, strlen($prefix))) + 1
                        : 1;

                    return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT); // e.g. ORD-260228-0001
                });
            }
        });
    }
}
