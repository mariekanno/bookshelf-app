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
    
        return view('favorites.index',compact('books'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Book $book)
    {
        auth()->user()
        ->favoriteBooks()
        ->syncWithoutDetaching([$book->id]);
    
        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        auth()->user()
        ->favoriteBooks()
        ->detach($book->id);
    
        return back();
    }
}
