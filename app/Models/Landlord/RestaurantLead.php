<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Model;

class RestaurantLead extends Model
{
    protected $connection = 'central';
    protected $table = 'restaurant_leads';

    protected $fillable = [
        'osm_id', 'name', 'type', 'address', 'city',
        'website', 'facebook', 'email', 'phone', 'maps_url',
        'contacted_at',
    ];

    protected $casts = [
        'contacted_at' => 'datetime',
    ];
}
