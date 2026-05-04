<?php

namespace App\Services\Geolocation;

use App\Models\Tenant\DeliveryZone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DeliveryZoneService
{
    protected ?string $googleMapsApiKey;

    public function __construct()
    {
        $this->googleMapsApiKey = config('services.google_maps.api_key');
    }

    /**
     * Geocode an address to get latitude and longitude
     * Uses Google Maps Geocoding API
     *
     * @param string $address
     * @return array|null ['lat' => float, 'lng' => float, 'formatted_address' => string]
     */
    public function geocodeAddress(string $address): ?array
    {
        if (!$this->googleMapsApiKey) {
            Log::warning('Google Maps API key not configured');
            return null;
        }

        // Cache geocoding results for 7 days (use store directly to avoid tagging issues)
        $cacheKey = 'geocode_' . md5($address);

        $doGeocode = function () use ($address) {
            try {
                $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $address,
                    'key' => $this->googleMapsApiKey,
                    'language' => 'pl',
                    'region' => 'pl',
                ]);

                $data = $response->json();

                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    $result = $data['results'][0];
                    $location = $result['geometry']['location'];

                    return [
                        'lat' => $location['lat'],
                        'lng' => $location['lng'],
                        'formatted_address' => $result['formatted_address'],
                    ];
                }

                Log::warning('Geocoding failed', [
                    'address' => $address,
                    'status' => $data['status'],
                ]);

                return null;

            } catch (\Exception $e) {
                Log::error('Geocoding exception', [
                    'address' => $address,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        };

        try {
            return Cache::store(config('cache.default'))->remember($cacheKey, 60 * 60 * 24 * 7, $doGeocode);
        } catch (\BadMethodCallException $e) {
            // Cache store doesn't support tagging — skip cache
            return $doGeocode();
        }
    }

    /**
     * Find delivery zone that contains the given address
     *
     * @param string $address
     * @return array|null ['zone' => DeliveryZone, 'coordinates' => array]
     */
    public function findZoneForAddress(string $address): ?array
    {
        $coordinates = $this->geocodeAddress($address);

        if (!$coordinates) {
            return null;
        }

        return $this->findZoneForCoordinates($coordinates['lat'], $coordinates['lng']);
    }

    /**
     * Find delivery zone that contains the given coordinates
     *
     * @param float $lat
     * @param float $lng
     * @return array|null ['zone' => DeliveryZone, 'coordinates' => array]
     */
    public function findZoneForCoordinates(float $lat, float $lng): ?array
    {
        $zones = DeliveryZone::active()->ordered()->get();

        foreach ($zones as $zone) {
            if ($zone->containsPoint($lat, $lng)) {
                return [
                    'zone' => $zone,
                    'coordinates' => [
                        'lat' => $lat,
                        'lng' => $lng,
                    ],
                ];
            }
        }

        return null;
    }

    /**
     * Validate if address is in delivery zone and meets minimum order value
     *
     * @param string $address
     * @param float $orderTotal
     * @return array ['valid' => bool, 'zone' => ?DeliveryZone, 'error' => ?string]
     */
    public function validateDeliveryAddress(string $address, float $orderTotal): array
    {
        // If no zones configured, allow delivery everywhere without restriction
        $activeZones = DeliveryZone::active()->count();
        if ($activeZones === 0) {
            return [
                'valid' => true,
                'zone' => null,
                'coordinates' => null,
                'error' => null,
            ];
        }

        // If API not configured, allow delivery (can't validate zone)
        if (!$this->isConfigured()) {
            return [
                'valid' => true,
                'zone' => null,
                'coordinates' => null,
                'error' => null,
            ];
        }

        $result = $this->findZoneForAddress($address);

        if (!$result) {
            return [
                'valid' => false,
                'zone' => null,
                'coordinates' => null,
                'error' => 'Niestety nie dowozimy pod ten adres. Sprawdź strefy dostawy lub wybierz odbiór osobisty.',
            ];
        }

        $zone = $result['zone'];

        // Check minimum order value
        if ($zone->min_order_value && $orderTotal < $zone->min_order_value) {
            return [
                'valid' => false,
                'zone' => $zone,
                'coordinates' => $result['coordinates'],
                'error' => "Minimalna wartość zamówienia dla tej strefy to {$zone->min_order_value} zł. Twoje zamówienie: {$orderTotal} zł.",
            ];
        }

        return [
            'valid' => true,
            'zone' => $zone,
            'coordinates' => $result['coordinates'],
            'error' => null,
        ];
    }

    /**
     * Calculate total delivery cost including zone fee
     *
     * @param DeliveryZone $zone
     * @return float
     */
    public function calculateDeliveryFee(DeliveryZone $zone): float
    {
        return (float) $zone->delivery_fee;
    }

    /**
     * Get all active delivery zones for map display
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllActiveZones()
    {
        return DeliveryZone::active()->ordered()->get();
    }

    /**
     * Check if Google Maps API is configured
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->googleMapsApiKey);
    }
}
