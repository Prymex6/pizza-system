<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    protected $fillable = [
        'number',
        'name',
        'seats',
        'min_guests',
        'location',
        'description',
        'qr_code',
        'qr_image_url',
        'is_active',
        'status',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'seats'      => 'integer',
        'min_guests' => 'integer',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
