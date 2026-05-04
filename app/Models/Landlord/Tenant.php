<?php

namespace App\Models\Landlord;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $connection = 'central';

    // Define which columns should be stored as actual DB columns (not in JSON data column)
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'subdomain',
            'custom_domain',
            'plan_id',
            'status',
            'version',
            'trial_ends_at',
            'license_ends_at',
        ];
    }

    protected $fillable = [
        'id',
        'name',
        'subdomain',
        'custom_domain',
        'plan_id',
        'status',
        'version',
        'trial_ends_at',
        'license_ends_at',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'trial_ends_at' => 'datetime',
        'license_ends_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOnTrial(): bool
    {
        return $this->status === 'trial' &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

}
