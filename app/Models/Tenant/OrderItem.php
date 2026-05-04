<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'name',
        'variant_name',
        'price',
        'quantity',
        'addons',
        'exclusions',
        'notes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'addons' => 'array',
        'exclusions' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function getTotalAttribute(): float
    {
        $total = $this->price * $this->quantity;

        if ($this->addons) {
            foreach ($this->addons as $addon) {
                $total += $addon['price'] * $this->quantity;
            }
        }

        return $total;
    }
}
