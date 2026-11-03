<?php

namespace App\Http\Controllers;

use App\Models\DoctorReview;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    public function index(Request $request)
    {
        $rating = $request->integer('rating');

        $reviews = DoctorReview::with(['doctor', 'patient'])
            ->when($rating, fn ($q) => $q->where('rating', $rating))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.reviews.index', compact('reviews', 'rating'));
    }

    public function toggle(DoctorReview $review)
    {
        $review->update(['is_visible' => !$review->is_visible]);
        return back()->with('success', $review->is_visible ? 'Review is visible again.' : 'Review hidden from patients.');
    }
}