<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Helpers\PhoneHelper;
use App\Models\Tenant\Setting;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SetupController extends Controller
{
    public function index()
    {
        // Redirect if setup already completed
        if (Setting::get('setup_completed', false)) {
            return redirect()->route('tenant.manager.dashboard');
        }

        return Inertia::render('Tenant/Manager/Setup', [
            'settings' => [
                'restaurant_name' => Setting::get('restaurant_name', ''),
                'restaurant_phone' => Setting::get('restaurant_phone', ''),
                'restaurant_email' => Setting::get('restaurant_email', ''),
                'restaurant_address' => Setting::get('restaurant_address', ''),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $step = $request->input('step');

        match ($step) {
            'restaurant' => $this->saveRestaurant($request),
            'hours'      => $this->saveHours($request),
            'complete'   => $this->complete($request),
            default      => null,
        };

        if ($step === 'complete') {
            Setting::set('setup_completed', true);
            return redirect()->route('tenant.manager.dashboard')
                ->with('success', 'Konfiguracja zakończona! Witaj w ' . config('app.name') . '.');
        }

        return back()->with('success', 'Zapisano.');
    }

    private function saveRestaurant(Request $request): void
    {
        $validated = $request->validate([
            'restaurant_name'    => ['required', 'string', 'max:255'],
            'restaurant_phone'   => ['nullable', 'string', 'max:30'],
            'restaurant_email'   => ['nullable', 'email', 'max:255'],
            'restaurant_address' => ['nullable', 'string', 'max:500'],
        ]);

        if (array_key_exists('restaurant_phone', $validated)) {
            $validated['restaurant_phone'] = PhoneHelper::normalize($validated['restaurant_phone']) ?? '';
        }

        foreach ($validated as $key => $value) {
            Setting::set($key, $value ?? '');
        }
    }

    private function saveHours(Request $request): void
    {
        $hours = $request->validate([
            'opening_hours' => ['nullable', 'array'],
        ])['opening_hours'] ?? [];

        Setting::set('opening_hours', json_encode($hours));
    }

    private function complete(Request $request): void
    {
        // Optionally create first category/product
        $categoryName = $request->input('category_name');
        if ($categoryName) {
            $category = Category::create([
                'name' => $categoryName,
                'sort_order' => 1,
            ]);

            $productName = $request->input('product_name');
            $productPrice = $request->input('product_price');
            if ($productName && $productPrice) {
                Product::create([
                    'category_id' => $category->id,
                    'name'        => $productName,
                    'price'       => $productPrice,
                    'is_available' => true,
                ]);
            }
        }
    }
}
