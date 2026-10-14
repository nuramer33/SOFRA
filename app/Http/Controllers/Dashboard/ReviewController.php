<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Every query is limited to the owner's shop by the tenant scope.
 */
class ReviewController extends Controller
{
    public function index(): View
    {
        return view('dashboard.reviews.index', [
            'reviews' => Review::query()->latest()->paginate(20),
            'average' => (float) Review::query()->avg('rating'),
            'total' => Review::query()->count(),
            'breakdown' => Review::query()->selectRaw('rating, count(*) as total')->groupBy('rating')->pluck('total', 'rating'),
        ]);
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return redirect()->route('reviews.index')->with('status', __('app.review.deleted'));
    }
}
