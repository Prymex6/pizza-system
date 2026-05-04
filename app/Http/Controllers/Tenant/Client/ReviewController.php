<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Events\ReviewCreated;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Order;
use App\Models\Tenant\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'order_id' => 'required|integer',
            'rating'   => 'required|integer|min:1|max:5',
            'content'  => 'required|string|max:2000',
        ]);

        // Verify order belongs to this customer and is completed
        $order = Order::where('id', $validated['order_id'])
            ->where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->firstOrFail();

        // One review per order
        if (Review::where('order_id', $order->id)->exists()) {
            return back()->withErrors(['order_id' => 'Już wystawiłeś ocenę dla tego zamówienia.']);
        }

        $review = Review::create([
            'order_id'    => $order->id,
            'customer_id' => $customer->id,
            'author_name' => $customer->name,
            'content'     => $validated['content'],
            'rating'      => $validated['rating'],
            'source'      => 'customer',
            'is_visible'  => false, // requires manager approval
            'sort_order'  => 0,
        ]);

        try {
            event(new ReviewCreated($review));
        } catch (\Exception $e) {
            // broadcasting failure must not block the response
        }

        return back()->with('success', 'Dziękujemy za ocenę!');
    }
}
