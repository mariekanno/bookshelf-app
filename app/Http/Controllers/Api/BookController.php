<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiIndexBookRequest;
use App\Http\Requests\ApiStoreBookRequest;
use App\Http\Requests\ApiUpdateBookRequest;
use App\Http\Resources\BookDetailResource;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ApiIndexBookRequest $request): AnonymousResourceCollection
    {
        $query = Book::with(['genres'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        if ($request->filled('keyword')) {
            $query->where(function ($query) use ($request) {
                $query->where('title', 'like', '%'.$request->keyword.'%')
                    ->orwhere('author', 'like', '%'.$request->keyword.'%');
            });
        }

        if ($request->filled('genres')) {
            $query->whereHas('genres', function ($query) use ($request) {
                $query->whereIn('genres.id', $request->genres);
            });
        }

        $books = $query
            ->latest()
            ->paginate($request->input('per_page', 10));

        return BookResource::collection($books);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ApiStoreBookRequest $request)
    {
        $validated = $request->validated();

        $genreIds = $validated['genres'];
        unset($validated['genres']);

        $book = Book::create($validated);

        $book->genres()->sync($genreIds);

        $book->load(['genres', 'reviews'])
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return new BookResource($book);
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book): BookDetailResource
    {
        $book->load(['genres', 'reviews.user'])
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return new BookDetailResource($book);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ApiUpdateBookRequest $request, Book $book)
    {
        $validated = $request->validated();

        $genreIds = $validated['genres'];
        unset($validated['genres']);

        $book->update($validated);

        $book->genres()->sync($genreIds);

        $book->load(['genres', 'reviews'])
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return new BookResource($book);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        $book->delete();

        return response()->noContent();
    }
}
