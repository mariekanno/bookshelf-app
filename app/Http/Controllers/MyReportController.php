<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\Review;

class MyReportController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $reviews = Review::where('user_id', $userId);

        $totalReviews = (clone $reviews)->count();

        $averageRating = (clone $reviews)->avg('rating') ?? 0;

        $booksRead = ReadingPlan::where('user_id', $userId)
            ->where('status', ReadingPlanStatus::Completed)
            ->distinct('book_id')
            ->count('book_id');

        $ratingDistribution = collect(range(1, 5))
            ->map(function (int $rating) use ($userId) {
                return Review::where('user_id', $userId)
                    ->where('rating', $rating)
                    ->count();
            });

        $topRatedBooks = Review::with('book')
            ->where('user_id', $userId)
            ->where('rating', '>=', 4)
            ->orderByDesc('rating')
            ->latest()
            ->get()
            ->unique('book_id')
            ->take(5)
            ->map(function (Review $review) {
                return [
                    'id' => $review->book->id,
                    'title' => $review->book->title,
                    'author' => $review->book->author,
                    'rating' => $review->rating,
                ];
            })
            ->values();

        $genreRatings = Review::with('book.genres')
            ->where('user_id', $userId)
            ->get()
            ->flatMap(function (Review $review) {
                return $review->book->genres->map(function ($genre) use ($review) {
                    return [
                        'id' => $genre->id,
                        'name' => $genre->name,
                        'rating' => $review->rating,
                    ];
                });
            })
            ->groupBy('id')
            ->map(function ($reviews) {
                return [
                    'id' => $reviews->first()['id'],
                    'name' => $reviews->first()['name'],
                    'count' => $reviews->count(),
                    'average_rating' => $reviews->avg('rating'),
                ];
            })
            ->sortByDesc('average_rating')
            ->take(5)
            ->values();

        $stats = [
            'summary' => [
                'total_reviews' => $totalReviews,
                'books_read' => $booksRead,
                'average_rating' => $averageRating,
            ],
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatings,
        ];

        return view('reports.index', compact('stats'));
    }
}
