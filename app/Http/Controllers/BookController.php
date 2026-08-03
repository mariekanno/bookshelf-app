<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use App\Services\GoogleBooksService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Book::with('genres')
            ->withAvg('reviews', 'rating');

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->keyword.'%')
                    ->orWhere('author', 'like', '%'.$request->keyword.'%');
            });
        }

        if ($request->filled('genre')) {
            $genreId = $request->input('genre');

            $query->whereHas('genres', function ($query) use ($genreId) {
                $query->where('genres.id', $genreId);
            });
        }

        switch ($request->input('sort', 'newest')) {
            case 'oldest':
                $query->oldest();
                break;

            case 'rating':
                $query->orderByDesc('reviews_avg_rating');
                break;

            case 'title':
                $query->orderBy('title');
                break;

            case 'newest':
            default:
                $query->latest();
                break;
        }

        $books = $query
            ->paginate(10)
            ->withQueryString();

        $genres = Genre::all();

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookRequest $request)
    {
        $validated = $request->validated();

        $genreIds = $validated['genres'];
        unset($validated['genres']);

        $validated['created_by'] = auth()->id();

        $book = Book::create($validated);

        $book->genres()->sync($genreIds);

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を登録しました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {
        $book->load([
            'genres',
            'reviews' => function ($query) {
                $query->with('user')
                    ->withCount('likes')
                    ->latest();
            },
        ])->loadCount([
            'favorites',
            'reviews',
        ]);

        return view('books.show', compact('book'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Book $book)
    {
        $this->authorize('update', $book);

        $genres = Genre::all();

        $book->load('genres');

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, Book $book)
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $genreIds = $validated['genres'];
        unset($validated['genres']);

        $book->update($validated);

        $book->genres()->sync($genreIds);

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍情報を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }

    public function searchByIsbn(
        string $isbn,
        GoogleBooksService $googleBooksService
    ): JsonResponse {
        if (! preg_match('/^\d{13}$/', $isbn)) {
            return response()->json([
                'error' => 'ISBNは13桁で入力してください。',
            ], 422);
        }

        $bookData = $googleBooksService->searchByIsbn($isbn);

        if ($bookData === null) {
            return response()->json([
                'error' => '該当する書籍が見つかりませんでした。',
            ], 404);
        }

        return response()->json($bookData);
    }
}
