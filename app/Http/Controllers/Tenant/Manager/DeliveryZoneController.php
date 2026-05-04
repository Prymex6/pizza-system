<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\DeliveryZone;
use App\Models\Tenant\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeliveryZoneController extends Controller
{
    public function index()
    {
        $zones = DeliveryZone::orderBy('sort_order')->orderBy('name')->get();

        return Inertia::render('Tenant/Manager/DeliveryZones/Index', [
            'zones' => $zones,
            'googleMapsConfigured' => !empty(config('services.google_maps.api_key')),
            'restaurantAddress' => trim(
                Setting::get('restaurant_address', '') . ' ' .
                Setting::get('restaurant_city', '')
            ),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'polygon' => 'required|array|min:3',
            'polygon.*' => 'required|array|size:2',
            'polygon.*.0' => 'required|numeric|between:-90,90',  // latitude
            'polygon.*.1' => 'required|numeric|between:-180,180',  // longitude
            'delivery_fee' => 'required|numeric|min:0',
            'min_order_value' => 'nullable|numeric|min:0',
            'estimated_time' => 'nullable|integer|min:1',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        DeliveryZone::create($validated);

        return back()->with('success', 'Strefa dostawy została dodana');
    }

    public function update(Request $request, DeliveryZone $deliveryZone)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'polygon' => 'required|array|min:3',
            'polygon.*' => 'required|array|size:2',
            'polygon.*.0' => 'required|numeric|between:-90,90',
            'polygon.*.1' => 'required|numeric|between:-180,180',
            'delivery_fee' => 'required|numeric|min:0',
            'min_order_value' => 'nullable|numeric|min:0',
            'estimated_time' => 'nullable|integer|min:1',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'boolean',
        ]);

        $deliveryZone->update($validated);

        return back()->with('success', 'Strefa dostawy została zaktualizowana');
    }

    public function destroy(DeliveryZone $deliveryZone)
    {
        $deliveryZone->delete();

        return back()->with('success', 'Strefa dostawy została usunięta');
    }
}
