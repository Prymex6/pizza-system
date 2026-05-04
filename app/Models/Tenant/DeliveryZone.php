<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    protected $table = 'tenant_delivery_zones';

    protected $fillable = [
        'name',
        'polygon',
        'delivery_fee',
        'min_order_value',
        'estimated_time',
        'color',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'polygon' => 'array',
        'delivery_fee' => 'decimal:2',
        'min_order_value' => 'decimal:2',
        'estimated_time' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope: only active zones
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: ordered by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Orders in this delivery zone
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'delivery_zone_id');
    }

    /**
     * Check if a point (lat, lng) is inside this zone's polygon
     * Using Ray Casting Algorithm
     */
    public function containsPoint(float $lat, float $lng): bool
    {
        $polygon = $this->polygon;

        if (!$polygon || count($polygon) < 3) {
            return false;
        }

        $inside = false;
        $j = count($polygon) - 1;

        for ($i = 0; $i < count($polygon); $i++) {
            $xi = $polygon[$i][0]; // lat
            $yi = $polygon[$i][1]; // lng
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];

            $intersect = (($yi > $lng) != ($yj > $lng))
                && ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }

            $j = $i;
        }

        return $inside;
    }

    /**
     * Get formatted delivery info
     */
    public function getDeliveryInfoAttribute(): string
    {
        $info = "Opłata za dostawę: {$this->delivery_fee} zł";

        if ($this->min_order_value) {
            $info .= " | Min. zamówienie: {$this->min_order_value} zł";
        }

        if ($this->estimated_time) {
            $info .= " | Czas dostawy: ~{$this->estimated_time} min";
        }

        return $info;
    }
}
