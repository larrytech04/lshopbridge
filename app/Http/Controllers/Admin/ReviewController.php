<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $query = Review::with('agent', 'user');
        if ($status = $request->query('status', 'pending')) {
            $query->where('status', $status);
        }

        return view('admin.reviews.index', [
            'reviews' => $query->latest()->paginate(20)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function approve(Review $review)
    {
        $review->update(['status' => 'approved', 'moderated_by' => auth()->id()]);
        $review->agent->recalculateRating();

        return back()->with('success', 'Review approved.');
    }

    public function reject(Review $review)
    {
        $review->update(['status' => 'rejected', 'moderated_by' => auth()->id()]);
        $review->agent->recalculateRating();

        return back()->with('success', 'Review rejected.');
    }
}
