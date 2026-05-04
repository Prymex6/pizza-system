<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'table_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'reservation_date',
        'reservation_time',
        'party_size',
        'notes',
        'status',
        'customer_id',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'party_size' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function scopeUpcoming($query)
    {
        $tz = \App\Models\Tenant\Setting::get('timezone', 'Europe/Warsaw');
        return $query->where('reservation_date', '>=', \Carbon\Carbon::now($tz)->toDateString())
            ->where('status', '!=', 'cancelled')
            ->orderBy('reservation_date')
            ->orderBy('reservation_time');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('reservation_date', $date);
    }
}
