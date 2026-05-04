<?php

namespace App\Models\Tenant;

use App\Notifications\StaffResetPasswordNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isChef(): bool
    {
        return $this->role === 'chef';
    }

    public function isWaiter(): bool
    {
        return $this->role === 'waiter';
    }

    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new StaffResetPasswordNotification($token));
    }
}
