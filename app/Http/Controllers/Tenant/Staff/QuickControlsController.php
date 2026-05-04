<?php

namespace App\Http\Controllers\Tenant\Staff;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Setting;
use Illuminate\Http\Request;

class QuickControlsController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'orders_paused'              => 'nullable|boolean',
            'estimated_preparation_time' => 'nullable|integer|min:5|max:180',
        ]);

        if (array_key_exists('orders_paused', $validated)) {
            Setting::set('orders_paused', (bool) $validated['orders_paused']);
        }

        if (array_key_exists('estimated_preparation_time', $validated) && $validated['estimated_preparation_time'] !== null) {
            Setting::set('estimated_preparation_time', (int) $validated['estimated_preparation_time']);
        }

        return response()->json([
            'success'                    => true,
            'orders_paused'              => (bool) Setting::get('orders_paused', false),
            'estimated_preparation_time' => (int) Setting::get('estimated_preparation_time', 30),
        ]);
    }
}
