<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'image',
        'ingredients',
        'allergens',
        'tags',
        'is_available',
        'is_featured',
        'sort_order',
        'track_stock',
        'stock_quantity',
    ];

    protected $casts = [
        'ingredients'    => 'array',
        'allergens'      => 'array',
        'tags'           => 'array',
        'is_available'   => 'boolean',
        'is_featured'    => 'boolean',
        'sort_order'     => 'integer',
        'track_stock'    => 'boolean',
        'stock_quantity' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(Addon::class, 'product_addon');
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)->where(function ($q) {
            $q->where('track_stock', false)->orWhere('stock_quantity', '>', 0);
        });
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('track_stock', false)
              ->orWhere('stock_quantity', '>', 0);
        });
    }

    public function decrementStock(int $quantity = 1): void
    {
        if ($this->track_stock && $this->stock_quantity > 0) {
            $this->decrement('stock_quantity', $quantity);
            if ($this->stock_quantity <= 0) {
                $this->update(['is_available' => false]);
            }
        }
    }
}
