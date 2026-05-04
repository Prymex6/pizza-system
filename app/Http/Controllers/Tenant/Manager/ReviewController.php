<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::with('order:id,order_number')->ordered()->paginate(20);

        return Inertia::render('Tenant/Manager/Reviews/Index', [
            'reviews' => $reviews,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'author_name' => 'required|string|max:255',
            'content' => 'required|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
            'source' => 'nullable|string|max:50',
            'is_visible' => 'boolean',
        ]);

        $validated['sort_order'] = Review::max('sort_order') + 1;

        Review::create($validated);

        return back()->with('success', 'Opinia została dodana.');
    }

    public function update(Request $request, Review $review)
    {
        $validated = $request->validate([
            'author_name' => 'required|string|max:255',
            'content' => 'required|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
            'source' => 'nullable|string|max:50',
            'is_visible' => 'boolean',
        ]);

        $review->update($validated);

        return back()->with('success', 'Opinia została zaktualizowana.');
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return back()->with('success', 'Opinia została usunięta.');
    }
}
