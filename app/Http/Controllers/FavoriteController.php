<?php

namespace App\Http\Controllers;

use App\Models\Book;

class FavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $books = auth()->user()
            ->favoriteBooks()
            ->latest('favorites.created_at')
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    public function toggle(Book $book)
    {
        auth()->user()
            ->favoriteBooks()
            ->toggle($book->id);

        return back();
    }
}
