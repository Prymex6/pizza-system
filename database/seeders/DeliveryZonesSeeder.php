<?php

namespace Database\Seeders;

use App\Models\Tenant\DeliveryZone;
use Illuminate\Database\Seeder;

class DeliveryZonesSeeder extends Seeder
{
    /**
     * Przykładowe strefy dostawy wokół centrum Krakowa.
     * Polygon = tablica par [lat, lng] wyznaczająca obszar.
     */
    public function run(): void
    {
        $zones = [
            [
                'name'             => 'Centrum',
                'polygon'          => [
                    [50.0657, 19.9445],
                    [50.0657, 19.9545],
                    [50.0557, 19.9545],
                    [50.0557, 19.9445],
                ],
                'delivery_fee'     => 5.00,
                'min_order_value'  => 30.00,
                'estimated_time'   => 25,
                'color'            => '#10B981',
                'is_active'        => true,
                'sort_order'       => 1,
            ],
            [
                'name'             => 'Dzielnice sąsiednie',
                'polygon'          => [
                    [50.0857, 19.9245],
                    [50.0857, 19.9745],
                    [50.0357, 19.9745],
                    [50.0357, 19.9245],
                ],
                'delivery_fee'     => 9.00,
                'min_order_value'  => 50.00,
                'estimated_time'   => 40,
                'color'            => '#F59E0B',
                'is_active'        => true,
                'sort_order'       => 2,
            ],
            [
                'name'             => 'Obrzeża miasta',
                'polygon'          => [
                    [50.1057, 19.8945],
                    [50.1057, 20.0045],
                    [50.0157, 20.0045],
                    [50.0157, 19.8945],
                ],
                'delivery_fee'     => 14.00,
                'min_order_value'  => 70.00,
                'estimated_time'   => 55,
                'color'            => '#EF4444',
                'is_active'        => true,
                'sort_order'       => 3,
            ],
        ];

        foreach ($zones as $zone) {
            DeliveryZone::updateOrCreate(
                ['name' => $zone['name']],
                $zone
            );
        }

        $this->command->line('  🚚 Strefy dostawy: ' . count($zones) . ' szt.');
    }
}
