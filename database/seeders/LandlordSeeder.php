<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LandlordSeeder extends Seeder
{
    public function run(): void
    {
        // ── Plany subskrypcji ──────────────────────────────────────────────
        $plans = [
            [
                'name'                 => 'Starter',
                'slug'                 => 'starter',
                'price'                => 49.00,
                'max_orders_per_month' => 300,
                'features'             => json_encode([
                    'delivery'          => true,
                    'pickup'            => true,
                    'dine_in'           => false,
                    'online_payments'   => false,
                    'loyalty_program'   => false,
                    'sms_notifications' => false,
                    'email_campaigns'   => false,
                    'custom_css'        => false,
                    'analytics'         => false,
                    'max_products'      => 30,
                    'max_staff'         => 3,
                    'delivery_zones'    => 2,
                ]),
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'                 => 'Basic',
                'slug'                 => 'basic',
                'price'                => 99.00,
                'max_orders_per_month' => 1000,
                'features'             => json_encode([
                    'delivery'          => true,
                    'pickup'            => true,
                    'dine_in'           => true,
                    'online_payments'   => true,
                    'loyalty_program'   => false,
                    'sms_notifications' => false,
                    'email_campaigns'   => false,
                    'custom_css'        => false,
                    'analytics'         => true,
                    'max_products'      => 100,
                    'max_staff'         => 10,
                    'delivery_zones'    => 5,
                ]),
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'                 => 'Pro',
                'slug'                 => 'pro',
                'price'                => 199.00,
                'max_orders_per_month' => 5000,
                'features'             => json_encode([
                    'delivery'          => true,
                    'pickup'            => true,
                    'dine_in'           => true,
                    'online_payments'   => true,
                    'loyalty_program'   => true,
                    'sms_notifications' => true,
                    'email_campaigns'   => true,
                    'custom_css'        => true,
                    'analytics'         => true,
                    'max_products'      => 500,
                    'max_staff'         => 30,
                    'delivery_zones'    => 15,
                    'thermal_printer'   => true,
                ]),
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'                 => 'Premium',
                'slug'                 => 'premium',
                'price'                => 399.00,
                'max_orders_per_month' => null,
                'features'             => json_encode([
                    'delivery'          => true,
                    'pickup'            => true,
                    'dine_in'           => true,
                    'online_payments'   => true,
                    'loyalty_program'   => true,
                    'sms_notifications' => true,
                    'email_campaigns'   => true,
                    'custom_css'        => true,
                    'analytics'         => true,
                    'max_products'      => null,
                    'max_staff'         => null,
                    'delivery_zones'    => null,
                    'thermal_printer'   => true,
                    'custom_domain'     => true,
                    'priority_support'  => true,
                    'api_access'        => true,
                ]),
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::connection('central')->table('plans')->insertOrIgnore($plans);

        // ── Super Admin ────────────────────────────────────────────────────
        DB::connection('central')->table('super_admins')->insertOrIgnore([
            'name'       => 'Administrator',
            'email'      => env('ADMIN_EMAIL', 'admin@example.com'),
            'password'   => Hash::make(env('ADMIN_PASSWORD', 'password')),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('  ✔ Plany i super admin gotowe.');
    }
}
