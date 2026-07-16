<?php

namespace App\Http\Controllers;

use App\Models\Review;

class ReviewLikeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function toggle(Review $review)
    {
        auth()->user()
            ->likedReviews()
            ->toggle($review->id);
        
        return back();
    }
}