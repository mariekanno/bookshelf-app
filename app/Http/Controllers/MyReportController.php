<?php

namespace App\Http\Controllers;

use App\Models\Review;

/**
 * ログインユーザーの読書実績を集計し、
 * マイ読書レポート画面へ表示するコントローラー。
 *
 * レビュー数・平均評価・読了冊数・高評価書籍など、
 * ユーザー自身のレビューをもとに統計情報を作成する。
 */
class MyReportController extends Controller
{
    /**
     * ログインユーザーの読書レポートを表示する。
     *
     * ユーザー自身のレビューを対象として各種統計を集計し、
     * レポート画面へ渡す。
     */
    public function index()
    {
        $userId = auth()->id();

        $reviews = Review::where('user_id', $userId);

        $totalReviews = (clone $reviews)->count();

        $averageRating = (clone $reviews)->avg('rating') ?? 0;

        $booksRead = Review::where('user_id', $userId)
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

        // 画面表示に必要な統計情報を項目ごとにまとめる
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
