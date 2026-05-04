<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $table = 'tenant_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value, string $type = 'string', ?string $description = null)
    {
        $stringValue = self::valueToString($value, $type);

        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'type' => $type,
                'description' => $description,
            ]
        );
    }

    /**
     * Check if a setting exists
     */
    public static function has(string $key): bool
    {
        return self::where('key', $key)->exists();
    }

    /**
     * Delete a setting
     */
    public static function forget(string $key): bool
    {
        return self::where('key', $key)->delete() > 0;
    }

    /**
     * Get all settings as key-value array
     */
    public static function getAllAsArray(): array
    {
        return self::query()
            ->get()
            ->mapWithKeys(function ($setting) {
                return [$setting->key => self::castValue($setting->value, $setting->type)];
            })
            ->toArray();
    }

    /** Keys stored encrypted (type='encrypted') */
    public const ENCRYPTED_KEYS = [
        'smtp_password',
        'p24_secret', 'p24_api_key',
        'stripe_secret_key', 'stripe_webhook_secret',
        'payu_secret_key',
        'tpay_secret',
        'smsapi_token',
    ];

    /**
     * Cast value based on type
     */
    protected static function castValue($value, string $type)
    {
        if ($value === null) {
            return null;
        }

        if ($type === 'encrypted') {
            try {
                return Crypt::decryptString($value);
            } catch (\Throwable) {
                return $value; // fallback for already plain-text legacy values
            }
        }

        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            'array' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Convert value to string for storage
     */
    protected static function valueToString($value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($type === 'encrypted') {
            return Crypt::encryptString((string) $value);
        }

        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'json', 'array' => json_encode($value),
            default => (string) $value,
        };
    }
}
